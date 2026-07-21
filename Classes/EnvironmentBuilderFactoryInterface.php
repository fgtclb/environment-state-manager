<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\EnvironmentStateManager\Exception\UnsupportedApplicationType;

/**
 * Interface for environment builder factory implementations.
 *
 * This interface is part of the public API. Type-hint it to retrieve a TYPO3 core-version
 * compatible environment builder for a given build context.
 */
interface EnvironmentBuilderFactoryInterface
{
    /**
     * Creates an environment builder instance.
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     * @throws UnsupportedApplicationType
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface;
}
