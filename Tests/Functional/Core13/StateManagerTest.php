<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core13;

use FGTCLB\EnvironmentStateManager\Core13\ExtendedStateInterface;
use FGTCLB\EnvironmentStateManager\Core13\State;
use FGTCLB\EnvironmentStateManager\Core13\StateManager;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

#[Group('not-core-12')]
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

    protected function createState(
        ?ServerRequestInterface $request = null,
        ?TypoScriptFrontendController $typoScriptFrontendController = null,
        ?BackendUserAuthentication $backendUserAuthentication = null,
    ): StateInterface {
        return new State(
            request: $request,
            typoScriptFrontendController: $typoScriptFrontendController,
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

    protected function readTypoScriptFrontendController(StateInterface $state): ?TypoScriptFrontendController
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

    protected function nonExtendedStateExceptionCode(): int
    {
        return 1762264322;
    }
}
