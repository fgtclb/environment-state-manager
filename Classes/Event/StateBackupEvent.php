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
 * @internal for internal use only; not part of the public API.
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
