<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * DTO carrying the build context configuration that defines how the
 * environment should be bootstrapped and prepared.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
final class StateBuildContext
{
    public function __construct(
        public readonly ApplicationType $applicationType,
        public readonly ?int $pageId = null,
        public readonly ?int $languageId = null,
    ) {}
}
