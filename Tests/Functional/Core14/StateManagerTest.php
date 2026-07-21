<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core14;

use FGTCLB\EnvironmentStateManager\Core14\State;
use FGTCLB\EnvironmentStateManager\Core14\StateManager;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

#[Group('not-core-13')]
final class StateManagerTest extends AbstractStateManagerTestCase
{
    protected function stateClass(): string
    {
        return State::class;
    }

    /**
     * TYPO3 v14 removed the TypoScriptFrontendController, the only TYPO3 core-version specific state
     * this extension carried, so there is no extended state interface for TYPO3 v14.
     */
    protected function extendedStateInterfaceClass(): ?string
    {
        return null;
    }

    protected function versionSpecificGlobalKey(): ?string
    {
        return null;
    }

    protected function createVersionSpecificGlobal(): ?object
    {
        return null;
    }

    protected function readVersionSpecificState(StateInterface $state): ?object
    {
        return null;
    }

    protected function createState(
        ?ServerRequestInterface $request = null,
        ?object $versionSpecificState = null,
        ?BackendUserAuthentication $backendUserAuthentication = null,
    ): StateInterface {
        return new State(
            request: $request,
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

    protected function assertBackedUpStateContext(StateInterface $state): void
    {
        // TYPO3 v14 does not seed a typoscript aspect on the default context; the preview-aspect
        // behaviour is covered by restoreSetsExpectedStateToEnvironment().
        $this->assertIsObject($state->context());
    }
}
