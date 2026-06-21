<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core12;

use FGTCLB\EnvironmentStateManager\Core12\ExtendedStateInterface;
use FGTCLB\EnvironmentStateManager\Core12\State;
use FGTCLB\EnvironmentStateManager\Core12\StateManager;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

#[Group('not-core-13')]
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
        $context = $state->context();
        $this->assertIsObject($context);
        // TYPO3 v12 seeds a typoscript aspect on the default context.
        $this->assertTrue($context->hasAspect('typoscript'));
    }

    protected function nonExtendedStateExceptionCode(): int
    {
        return 1762264455;
    }
}
