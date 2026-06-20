<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Default environment builder factory implementation for {@see EnvironmentBuilderFactoryInterface}.
 *
 * @internal Concrete implementation of {@see EnvironmentBuilderFactoryInterface}. Resolved through
 *           dependency injection — type-hint the interface, not this class. Not covered by the
 *           extension's public-API backward-compatibility promise.
 */
#[Exclude]
final class EnvironmentBuilderFactory implements EnvironmentBuilderFactoryInterface
{
    public function __construct(
        private readonly EnvironmentBuilderInterface $frontendEnvironmentBuilder,
        private readonly EnvironmentBuilderInterface $backendEnvironmentBuilder,
    ) {}

    /**
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        return match ($stateBuildContext->applicationType) {
            ApplicationType::FRONTEND => $this->frontendEnvironmentBuilder,
            ApplicationType::BACKEND => $this->backendEnvironmentBuilder,
            // ApplicationType has only two cases, so no default branch is needed. Omitting it keeps PHPStan happy.
        };
    }
}
