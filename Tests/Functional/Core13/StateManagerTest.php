<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core13;

use FGTCLB\EnvironmentStateManager\Core13\ExtendedStateInterface;
use FGTCLB\EnvironmentStateManager\Core13\State;
use FGTCLB\EnvironmentStateManager\Core13\StateManager;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

#[Group('not-core-14')]
final class StateManagerTest extends AbstractStateManagerTestCase
{
    protected function stateClass(): string
    {
        return State::class;
    }

    protected function extendedStateInterfaceClass(): string
    {
        return ExtendedStateInterface::class;
    }

    protected function versionSpecificGlobalKey(): string
    {
        return 'TSFE';
    }

    protected function createVersionSpecificGlobal(): object
    {
        return $this->createMock(TypoScriptFrontendController::class);
    }

    /**
     * The restore test needs a TypoScriptFrontendController carrying a ContentObjectRenderer,
     * because applying a TYPO3 v13 state re-creates it through `newCObj()`.
     */
    protected function createVersionSpecificGlobalForRestore(): object
    {
        $typoScriptFrontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMock->cObj = GeneralUtility::makeInstance(
            ContentObjectRenderer::class,
            $typoScriptFrontendControllerMock,
        );
        return $typoScriptFrontendControllerMock;
    }

    protected function createState(
        ?ServerRequestInterface $request = null,
        ?object $versionSpecificState = null,
        ?BackendUserAuthentication $backendUserAuthentication = null,
    ): StateInterface {
        $this->assertTrue(
            $versionSpecificState === null || $versionSpecificState instanceof TypoScriptFrontendController,
        );
        return new State(
            request: $request,
            typoScriptFrontendController: $versionSpecificState,
            backendUserAuthentication: $backendUserAuthentication,
        );
    }

    protected function instantiateStateManager(
        EnvironmentBuilderFactoryInterface $environmentBuilderFactory,
    ): StateManagerInterface {
        return new StateManager(
            environmentBuilderFactory: $environmentBuilderFactory,
        );
    }

    protected function readVersionSpecificState(StateInterface $state): ?object
    {
        $this->assertInstanceOf(ExtendedStateInterface::class, $state);
        return $state->typoScriptFrontendController();
    }

    protected function assertBackedUpStateContext(StateInterface $state): void
    {
        // TYPO3 v13 does not seed a typoscript aspect on the default context; the preview-aspect
        // behaviour is covered by restoreSetsExpectedStateToEnvironment().
        $this->assertIsObject($state->context());
    }

    /**
     * TYPO3 v13 specific: bootstrap() requires the builder to return an extended state carrying the
     * TypoScriptFrontendController. TYPO3 v14 has no extended state, so this lives here.
     */
    #[Test]
    public function bootstrapThrowsWhenBuilderReturnsNonExtendedState(): void
    {
        $nonExtendedState = $this->createMock(StateInterface::class);
        $environmentBuilderMock = $this->createEnvironmentBuilderMock();
        $environmentBuilderMock->method('build')->willReturn($nonExtendedState);
        $stateManager = $this->createStateManager($this->createEnvironmentBuilderFactoryMock($environmentBuilderMock));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1762264322);

        $stateManager->bootstrap(new StateBuildContext(applicationType: ApplicationType::FRONTEND));
    }
}
