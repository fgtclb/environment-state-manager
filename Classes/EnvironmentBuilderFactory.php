<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Default environment builder factory implementation for {@see EnvironmentBuilderFactoryInterface}.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
#[Exclude]
final class EnvironmentBuilderFactory implements EnvironmentBuilderFactoryInterface
{
    public function __construct(
        private readonly EnvironmentBuilderInterface $frontendEnvironmentBuilder,
    ) {}

    /**
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        return match ($stateBuildContext->applicationType) {
            ApplicationType::FRONTEND => $this->frontendEnvironmentBuilder,
            ApplicationType::BACKEND => $this->notImplemented($stateBuildContext),
            // ApplicationType has only two cases, so no default branch is needed. Omitting it keeps PHPStan happy.
        };
    }

    private function notImplemented(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        throw new \RuntimeException(
            sprintf(
                'Not implemented yet for applicationType "%s".',
                $stateBuildContext->applicationType->value,
            ),
            1762256802,
        );
    }
}
