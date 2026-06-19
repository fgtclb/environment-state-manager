<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

/**
 * Provides shared {@see StateManagerInterface::execute()} implementation to be used in
 * {@see StateManagerInterface} implementations depending on to reduce code duplication.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
trait StateManagerExecuteMethodTrait
{
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

        // Create current environment state snapshot.
        $this->backup();
        try {
            // Bootstrap environment based on state build context.
            $this->bootstrap($stateBuildContext);
            // Execute closure.
            $work();
        } finally {
            // Ensure to restore previous environment state.
            $this->restore();
        }
    }
}
