<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\Exception\SiteConfigCouldNotBeDetermined;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;

final class FrontendEnvironmentBuilderTest extends AbstractEnvironmentStateManagerTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $GLOBALS['TSFE'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REMOTE_ADDR'],
        );
        parent::tearDown();
    }

    private function buildFrontendState(StateBuildContext $stateBuildContext): StateInterface
    {
        return $this->get(EnvironmentBuilderFactoryInterface::class)
            ->create($stateBuildContext)
            ->build($stateBuildContext);
    }

    #[Test]
    public function buildThrowsWhenNoSiteConfigurationExistsForNullPageId(): void
    {
        $this->expectException(SiteConfigCouldNotBeDetermined::class);
        $this->expectExceptionCode(1762255830);

        $this->buildFrontendState(new StateBuildContext(applicationType: ApplicationType::FRONTEND));
    }

    #[Test]
    public function buildThrowsWhenMultipleSiteConfigurationsExistForNullPageId(): void
    {
        $this->writeSiteConfiguration(
            'first',
            $this->buildSiteConfiguration(1, 'https://first.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://first.example.com/')],
        );
        $this->writeSiteConfiguration(
            'second',
            $this->buildSiteConfiguration(2, 'https://second.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://second.example.com/')],
        );

        $this->expectException(SiteConfigCouldNotBeDetermined::class);
        $this->expectExceptionCode(1762255738);

        $this->buildFrontendState(new StateBuildContext(applicationType: ApplicationType::FRONTEND));
    }

    #[Test]
    public function buildThrowsWhenNoSiteConfigurationMatchesGivenPageId(): void
    {
        $this->expectException(SiteConfigCouldNotBeDetermined::class);
        $this->expectExceptionCode(1762255989);

        $this->buildFrontendState(new StateBuildContext(applicationType: ApplicationType::FRONTEND, pageId: 4711));
    }

    #[Test]
    public function buildCreatesFrontendEnvironmentForRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendEnvironment/pages.csv');
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://example.com/')],
        );
        $this->setUpFrontendRootPage(1, ['setup' => ['EXT:environment_state_manager/Tests/Functional/Fixtures/FrontendEnvironment/setup.typoscript']]);

        $state = $this->buildFrontendState(new StateBuildContext(
            applicationType: ApplicationType::FRONTEND,
            pageId: 1,
        ));

        $request = $state->request();
        $this->assertNotNull($request);
        $this->assertSame(SystemEnvironmentBuilder::REQUESTTYPE_FE, $request->getAttribute('applicationType'));
        $this->assertTrue(ApplicationType::fromRequest($request)->isFrontend());

        $this->assertInstanceOf(Context::class, $state->context());

        // The synthetic $_SERVER carries the site host and a seeded remote address.
        $serverParams = $state->additionalData('_SERVER');
        $this->assertIsArray($serverParams);
        $this->assertSame('example.com', $serverParams['HTTP_HOST'] ?? null);
        $this->assertArrayHasKey('REMOTE_ADDR', $serverParams);
        $this->assertNotSame('', $serverParams['REMOTE_ADDR'] ?? '');

        // Building must not touch the global environment.
        $this->assertArrayNotHasKey('TSFE', $GLOBALS);
        $this->assertArrayNotHasKey('TYPO3_REQUEST', $GLOBALS);
    }
}
