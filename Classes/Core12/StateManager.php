<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerExecuteMethodTrait;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\StateManagerRootStateInterfaceHelperMethodsTrait;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Default implementation of {@see StateManagerInterface} for  TYPO3 v12.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:environment_state_manager/Configuration/Services.php` file.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
#[Exclude]
final class StateManager implements StateManagerInterface
{
    use StateManagerRootStateInterfaceHelperMethodsTrait;
    use StateManagerExecuteMethodTrait;

    /**
     * @var State[]
     */
    private array $stack = [];

    public function __construct(
        private readonly EnvironmentBuilderFactoryInterface $environmentBuilderFactory,
    ) {}

    /**
     * Create a backup of the current environment and add it on top of the snapshot stack.
     */
    public function backup(): void
    {
        $state = $this->backupStateInterface(new State());
        if ($state instanceof ExtendedStateInterface) {
            // no special handling right now required based on extended interface
        }
        $state = $this->dispatchStateBackupEvent($state);
        /** @var State $state */
        array_push($this->stack, $state);
    }

    /**
     * Reset the environment to an empty state.
     *
     * **Be aware** that this method does not make a backup nor restores the current environment.
     */
    public function reset(): void
    {
        $this->apply(new State());
    }

    /**
     * Restore the last environment and remove it from the snapshot stack.
     */
    public function restore(): void
    {
        /** @var State $state */
        $state = array_pop($this->stack) ?? new State();
        $this->apply($state);
    }

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
    public function bootstrap(StateBuildContext $stateBuildContext): StateInterface
    {
        $state = $this->environmentBuilderFactory->create($stateBuildContext)->build($stateBuildContext);
        if (!in_array(ExtendedStateInterface::class, class_implements($state), true)) {
            throw new \RuntimeException(
                sprintf(
                    'Class "%s" does not implement extended interface "%s".',
                    $state::class,
                    ExtendedStateInterface::class,
                ),
                1762264455,
            );
        }
        $this->apply($state);
        return $state;
    }

    /**
     * Apply provided state to the environment.
     *
     * **Be aware** that this method changes the environment without doing and backup
     * of it nor restores it. See {@see StateManagerInterface::backup()} and method
     * {@see StateManagerInterface::restore()} for snapshot handling.
     */
    public function apply(StateInterface $state): void
    {
        $this->applyStateInterface($state);
        if ($state instanceof ExtendedStateInterface) {
            // no special handling right now required based on extended interface
        }
        $this->dispatchStateApplyEvent($state);
    }
}
