<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

/**
 * Provides a shared {@see StateManagerInterface::execute()} implementation that
 * {@see StateManagerInterface} implementations can rely on to reduce code duplication.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
trait StateManagerExecuteMethodTrait
{
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
    public function execute(StateBuildContext $stateBuildContext, \Closure $work): void
    {
        if (!$this instanceof StateManagerInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Trait "%s" must be only used on classes implementing "%s", provided "%s" does not.',
                    StateManagerRootStateInterfaceHelperMethodsTrait::class,
                    StateManagerInterface::class,
                    static::class,
                ),
                1762436800,
            );
        }

        // Create a snapshot of the current environment state.
        $this->backup();
        try {
            // Bootstrap the environment based on the state build context.
            $this->bootstrap($stateBuildContext);
            // Execute the closure.
            $work();
        } finally {
            // Make sure the previous environment state is restored.
            $this->restore();
        }
    }
}
