<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

/**
 * Interface for concrete environment builder implementations.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
interface EnvironmentBuilderInterface
{
    /**
     * Build environment configured by passed $buildContext.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface;
}
