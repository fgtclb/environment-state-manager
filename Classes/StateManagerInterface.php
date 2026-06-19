<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

/**
 * Describes the mandatory methods of a environment state manager implementation.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
interface StateManagerInterface
{
    /**
     * Create a backup of the current environment and add it on top of the snapshot stack.
     */
    public function backup(): void;

    /**
     * Restore the last environment and remove it from the snapshot stack.
     */
    public function restore(): void;

    /**
     * Reset the environment to an empty state.
     *
     * **Be aware** that this method does not make a backup nor restores the current environment.
     */
    public function reset(): void;

    /**
     * Create a state for `$pageId` and populate the environment with it,
     * returning the created state elements as {@see StateInterface}.
     *
     * **Be aware** that this method changes the environment without doing and backup
     * of it nor restores it if {@see StateBuildContext::$autoApplyBootstrappedEnvironment}
     * is set to true. For snapshot handling see following methods:
     *
     * - {@see StateManagerInterface::backup()}
     * - {@see StateManagerInterface::restore()}
     *
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function bootstrap(StateBuildContext $stateBuildContext): StateInterface;

    /**
     * Apply provided state to the environment.
     *
     * **Be aware** that this method changes the environment without doing and backup
     * of it nor restores it. See {@see StateManagerInterface::backup()} and method
     * {@see StateManagerInterface::restore()} for snapshot handling.
     */
    public function apply(StateInterface $state): void;

    /**
     * Execute code ($work closure) in the environment defined by $stateBuildContext,
     * which includes following steps:
     *
     * - backup current environment state
     * - bootstrap environment state described by $stateBuildContext
     * - execute $work closure
     * - restore environment state snapshot
     *
     * The implementation **must** ensure that environment restore is executed in any case, even if
     * $work closure execution throws any error or exception, for example using `try {} finally {}.`
     */
    public function execute(StateBuildContext $stateBuildContext, \Closure $work): void;
}
