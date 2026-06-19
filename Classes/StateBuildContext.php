<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Environment build context configuration DTO used to configure
 * how the environment should be bootstrapped and prepared.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
final class StateBuildContext
{
    public function __construct(
        public readonly ApplicationType $applicationType,
        public readonly ?int $pageId = null,
        public readonly ?int $languageId = null,
    ) {}
}
