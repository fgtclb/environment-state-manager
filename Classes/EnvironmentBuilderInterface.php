<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

/**
 * Interface for concrete environment builder implementations.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
interface EnvironmentBuilderInterface
{
    /**
     * Builds the environment as configured by the given build context.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface;
}
