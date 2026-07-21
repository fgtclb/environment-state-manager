<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\EnvironmentStateManager\Exception\UnsupportedApplicationType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Default environment builder factory implementation for {@see EnvironmentBuilderFactoryInterface}.
 *
 * This factory is version-agnostic and lives in the `Classes/` folder. It is registered as a public
 * dependency injection service (`#[Autoconfigure(public: true)]`) and published as the public API
 * {@see EnvironmentBuilderFactoryInterface} via `#[AsAlias]`.
 *
 * @internal Concrete implementation of {@see EnvironmentBuilderFactoryInterface}. Resolved through
 *           dependency injection — type-hint the interface, not this class. Not covered by the
 *           extension's public-API backward-compatibility promise.
 */
#[Autoconfigure(public: true)]
#[AsAlias(id: EnvironmentBuilderFactoryInterface::class, public: true)]
final class EnvironmentBuilderFactory implements EnvironmentBuilderFactoryInterface
{
    /**
     * Both builders share the {@see EnvironmentBuilderInterface} type, which autowiring cannot tell
     * apart. They are therefore injected explicitly via `#[Autowire(service: ...)]` from the stable,
     * version-independent service ids that the concrete `CoreNN` frontend and backend builders
     * publish through their `#[AsAlias]` attribute — resolving to whichever `Core{major}/` folder is
     * loaded for the running TYPO3 version.
     */
    public function __construct(
        #[Autowire(service: 'fgtclb.environment_state_manager.frontend_environment_builder')]
        private readonly EnvironmentBuilderInterface $frontendEnvironmentBuilder,
        #[Autowire(service: 'fgtclb.environment_state_manager.backend_environment_builder')]
        private readonly EnvironmentBuilderInterface $backendEnvironmentBuilder,
    ) {}

    /**
     * Note that this implementation does not throw
     * {@see NoTypo3VersionCompatibleEnvironmentBuilderFound} itself: the builders are injected by
     * the dependency injection container, which fails earlier when no `Core{major}/` folder matches
     * the running TYPO3 version. The exception stays part of the interface contract for
     * implementations resolving the builders lazily.
     *
     * The {@see UnsupportedApplicationType} declared on the interface is thrown only on TYPO3 v14+,
     * where `ApplicationType::INSTALL` exists; on TYPO3 v13 the enum has only frontend and backend,
     * so the guard below is unreachable and the tag is intentionally omitted here.
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        // Keyed by application type value ('frontend', 'backend'). TYPO3 v14 added
        // `ApplicationType::INSTALL`, for which no environment is built; a missing key therefore
        // maps to an unsupported application type on every supported TYPO3 version.
        // @var array<non-empty-string, EnvironmentBuilderInterface> $builders
        $builders = [
            ApplicationType::FRONTEND->value => $this->frontendEnvironmentBuilder,
            ApplicationType::BACKEND->value => $this->backendEnvironmentBuilder,
        ];
        $applicationType = $stateBuildContext->applicationType->value;
        if (!array_key_exists($applicationType, $builders)) {
            throw new UnsupportedApplicationType(
                sprintf('No environment builder available for application type "%s".', $applicationType),
                1784672688,
            );
        }
        return $builders[$applicationType];
    }
}
