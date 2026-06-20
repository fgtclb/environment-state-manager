<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

/**
 * Describes the methods an environment state manager implementation must provide.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
interface StateManagerInterface
{
    /**
     * Creates a backup of the current environment and pushes it onto the snapshot stack.
     */
    public function backup(): void;

    /**
     * Restore the last environment and remove it from the snapshot stack.
     */
    public function restore(): void;

    /**
     * Reset the environment to an empty state.
     *
     * **Be aware** that this method neither backs up nor restores the current environment.
     */
    public function reset(): void;

    /**
     * Creates a state for `$pageId` and populates the environment with it,
     * returning the created state as {@see StateInterface}.
     *
     * **Be aware** that this method changes the environment without creating a backup
     * of it or restoring it when {@see StateBuildContext::$autoApplyBootstrappedEnvironment}
     * is set to true. For snapshot handling, see the following methods:
     *
     * - {@see StateManagerInterface::backup()}
     * - {@see StateManagerInterface::restore()}
     *
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function bootstrap(StateBuildContext $stateBuildContext): StateInterface;

    /**
     * Applies the given state to the environment.
     *
     * **Be aware** that this method changes the environment without creating a backup
     * of it or restoring it. See {@see StateManagerInterface::backup()} and
     * {@see StateManagerInterface::restore()} for snapshot handling.
     */
    public function apply(StateInterface $state): void;

    /**
     * Executes code (the $work closure) in the environment defined by $stateBuildContext.
     * This involves the following steps:
     *
     * - back up the current environment state
     * - bootstrap the environment state described by $stateBuildContext
     * - execute the $work closure
     * - restore the environment state snapshot
     *
     * The implementation **must** make sure the environment is restored in every case, even when the
     * $work closure throws an error or exception — for example by using `try {} finally {}`.
     */
    public function execute(StateBuildContext $stateBuildContext, \Closure $work): void;
}
