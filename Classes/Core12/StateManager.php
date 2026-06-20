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
 * Default implementation of {@see StateManagerInterface} for TYPO3 v12.
 *
 * The `#[Exclude]` attribute is set on purpose. It keeps this class from being compiled
 * early into the dependency injection container, which would otherwise trigger missing-class
 * and similar errors for unrelated TYPO3 versions. The TYPO3 version-aware configuration is
 * handled and re-enabled in the `EXT:environment_state_manager/Configuration/Services.php` file.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
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
     * Creates a backup of the current environment and pushes it onto the snapshot stack.
     */
    public function backup(): void
    {
        $state = $this->backupStateInterface(new State());
        if ($state instanceof ExtendedStateInterface) {
            // No special handling is required for the extended interface at the moment.
        }
        $state = $this->dispatchStateBackupEvent($state);
        /** @var State $state */
        array_push($this->stack, $state);
    }

    /**
     * Reset the environment to an empty state.
     *
     * **Be aware** that this method neither backs up nor restores the current environment.
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
     * Applies the given state to the environment.
     *
     * **Be aware** that this method changes the environment without creating a backup
     * of it or restoring it. See {@see StateManagerInterface::backup()} and
     * {@see StateManagerInterface::restore()} for snapshot handling.
     */
    public function apply(StateInterface $state): void
    {
        $this->applyStateInterface($state);
        if ($state instanceof ExtendedStateInterface) {
            // No special handling is required for the extended interface at the moment.
        }
        $this->dispatchStateApplyEvent($state);
    }
}
