<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

final class BackendEnvironmentBuilderTest extends AbstractEnvironmentStateManagerTestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $GLOBALS['TSFE'],
            $GLOBALS['BE_USER'],
            $GLOBALS['LANG'],
        );
        parent::tearDown();
    }

    private function buildBackendState(StateBuildContext $stateBuildContext): StateInterface
    {
        return $this->get(EnvironmentBuilderFactoryInterface::class)
            ->create($stateBuildContext)
            ->build($stateBuildContext);
    }

    #[Test]
    public function buildCreatesBackendEnvironmentWithSyntheticAdmin(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/pages.csv');

        $state = $this->buildBackendState(new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: 1,
        ));

        $request = $state->request();
        $this->assertNotNull($request);
        $this->assertSame(SystemEnvironmentBuilder::REQUESTTYPE_BE, $request->getAttribute('applicationType'));
        $this->assertTrue(ApplicationType::fromRequest($request)->isBackend());

        $backendUser = $state->backendUserAuthentication();
        $this->assertInstanceOf(BackendUserAuthentication::class, $backendUser);
        $this->assertTrue($backendUser->isAdmin());

        $this->assertInstanceOf(LanguageService::class, $state->languageService());

        $context = $state->context();
        $this->assertInstanceOf(Context::class, $context);
        $this->assertTrue($context->getAspect('backend.user')->isAdmin());
        $this->assertSame(0, $context->getAspect('workspace')->get('id'));

        $backendData = $state->additionalData('backend');
        $this->assertIsArray($backendData);
        $this->assertSame(1, $backendData['pageId']);
        $this->assertSame(0, $backendData['workspaceId']);
        $this->assertInstanceOf(SiteInterface::class, $backendData['site']);

        // Building must not touch the global environment.
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);
        $this->assertArrayNotHasKey('LANG', $GLOBALS);
    }

    #[Test]
    public function buildLoadsConfiguredBackendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/be_users.csv');

        $state = $this->buildBackendState(new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: 1,
            backendUserId: 1,
        ));

        $backendUser = $state->backendUserAuthentication();
        $this->assertInstanceOf(BackendUserAuthentication::class, $backendUser);
        $this->assertSame(1, (int)($backendUser->user['uid'] ?? 0));
        $this->assertSame('admin', $backendUser->user['username'] ?? null);
        $this->assertTrue($backendUser->isAdmin());
    }

    #[Test]
    public function buildAppliesRequestedWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/pages.csv');

        $state = $this->buildBackendState(new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: 1,
            workspaceId: 2,
        ));

        $backendUser = $state->backendUserAuthentication();
        $this->assertInstanceOf(BackendUserAuthentication::class, $backendUser);
        $this->assertSame(2, $backendUser->workspace);

        $context = $state->context();
        $this->assertInstanceOf(Context::class, $context);
        $this->assertSame(2, $context->getAspect('workspace')->get('id'));
    }

    #[Test]
    public function executingInBackendEnvironmentResolvesPageTsConfigAndRestoresGlobals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/pages.csv');

        // Before: clean environment.
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);

        $resolvedPageTsConfig = null;
        $backendUserInsideClosure = null;
        $this->get(StateManagerInterface::class)->execute(
            new StateBuildContext(applicationType: ApplicationType::BACKEND, pageId: 2),
            function () use (&$resolvedPageTsConfig, &$backendUserInsideClosure): void {
                $backendUserInsideClosure = $GLOBALS['BE_USER'] ?? null;
                $resolvedPageTsConfig = BackendUtility::getPagesTSconfig(2);
            }
        );

        // Inside the closure the backend environment was active ...
        $this->assertInstanceOf(BackendUserAuthentication::class, $backendUserInsideClosure);
        $this->assertIsArray($resolvedPageTsConfig);
        // ... and the page TSconfig of the selected page (and its rootline) resolved.
        $this->assertSame('123', $resolvedPageTsConfig['TCEMAIN.']['test'] ?? null);
        $this->assertSame('456', $resolvedPageTsConfig['TCEMAIN.']['sub'] ?? null);

        // After: the previous (empty) environment is restored.
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);
        $this->assertArrayNotHasKey('LANG', $GLOBALS);
    }

    #[Test]
    public function executeRestoresEnvironmentWhenClosureThrows(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendEnvironment/pages.csv');

        // Before: clean environment.
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);

        $backendUserInsideClosure = null;
        $caughtException = null;
        try {
            $this->get(StateManagerInterface::class)->execute(
                new StateBuildContext(applicationType: ApplicationType::BACKEND, pageId: 1),
                function () use (&$backendUserInsideClosure): void {
                    $backendUserInsideClosure = $GLOBALS['BE_USER'] ?? null;
                    throw new \RuntimeException('failure inside the managed environment', 1762700000);
                }
            );
        } catch (\RuntimeException $exception) {
            $caughtException = $exception;
        }

        // The closure ran inside an active backend environment ...
        $this->assertInstanceOf(BackendUserAuthentication::class, $backendUserInsideClosure);
        // ... the exception propagated out of execute() ...
        $this->assertInstanceOf(\RuntimeException::class, $caughtException);
        $this->assertSame(1762700000, $caughtException->getCode());
        // ... and the previous (empty) environment was restored despite the exception.
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);
        $this->assertArrayNotHasKey('LANG', $GLOBALS);
    }
}
