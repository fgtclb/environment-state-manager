<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Event;

use FGTCLB\EnvironmentStateManager\StateInterface;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateBackupEvent()}.
 * TYPO3 version-specific implementations can use it to back up custom state data through the generic
 * additional state exposed by {@see StateInterface::additionalData()}, {@see StateInterface::completeAdditionalData()}
 * and {@see StateInterface::withAdditionalData()}.
 *
 * This event is part of the public API. Register a PSR-14 listener to react when the current
 * environment is backed up onto the snapshot stack.
 */
final class StateBackupEvent
{
    public function __construct(
        private StateInterface $state,
    ) {}

    public function getState(): StateInterface
    {
        return $this->state;
    }

    public function setState(StateInterface $state): self
    {
        $this->state = $state;
        return $this;
    }
}
