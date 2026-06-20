<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

/**
 * Interface for environment builder factory implementations.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
interface EnvironmentBuilderFactoryInterface
{
    /**
     * Creates an environment builder instance.
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface;
}
