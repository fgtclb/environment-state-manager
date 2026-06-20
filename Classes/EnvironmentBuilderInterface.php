<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

/**
 * Interface for concrete environment builder implementations.
 *
 * This interface is part of the public API. The concrete builder differs between supported TYPO3
 * core versions and is resolved through {@see EnvironmentBuilderFactoryInterface}; type-hint this
 * interface instead of a concrete `Core*` implementation.
 */
interface EnvironmentBuilderInterface
{
    /**
     * Builds the environment as configured by the given build context.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface;
}
