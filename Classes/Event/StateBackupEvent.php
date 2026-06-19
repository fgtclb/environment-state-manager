<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Event;

use FGTCLB\EnvironmentStateManager\StateInterface;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateBackupEvent()},
 * used by TYPO3 version related implementation to allow backup custom state data using generic additiona state
 * provided with {@see StateInterface::additionalData()}, {@see StateInterface::completeAdditionalData()} and
 * {@see StateInterface::withAdditionalData()}.
 *
 * @internal for internal usage only and not part of public API.
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
