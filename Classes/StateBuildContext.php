<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * DTO carrying the build context configuration that defines how the
 * environment should be bootstrapped and prepared.
 *
 * This DTO is part of the public API and is instantiated directly by consumers to describe the
 * environment to build.
 */
final class StateBuildContext
{
    public function __construct(
        public readonly ApplicationType $applicationType,
        public readonly ?int $pageId = null,
        public readonly ?int $languageId = null,
    ) {}
}
