<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Event;

use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerRootStateInterfaceHelperMethodsTrait;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateApplyEvent()}.
 * TYPO3 version-specific implementations can use it to apply additional custom state, for example.
 *
 * This event is part of the public API. Register a PSR-14 listener to react when a state is applied
 * to the global environment.
 */
final class StateApplyEvent
{
    public function __construct(
        private readonly StateInterface $state,
    ) {}

    public function getState(): StateInterface
    {
        return $this->state;
    }
}
