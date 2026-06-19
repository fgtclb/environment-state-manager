<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Event;

use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerRootStateInterfaceHelperMethodsTrait;

/**
 * This event is dispatched in {@see StateManagerRootStateInterfaceHelperMethodsTrait::dispatchStateApplyEvent()},
 * used by TYPO3 version related implementation to allow applying for example additional custom state.
 *
 * @internal for internal usage only and not part of public API.
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
