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
    /**
     * @param ApplicationType $applicationType Whether a frontend or backend environment is built.
     * @param int|null $pageId The page the environment is built for. `null` or `0` lets the builder
     *                         pick the single available site (frontend) or skip site resolution.
     * @param int|null $languageId The language to use. `null` falls back to the default language.
     * @param int|null $backendUserId Backend only: the backend user to load into the environment.
     *                                `null` uses a synthetic in-memory admin user; a uid loads that
     *                                existing backend user. Ignored for frontend environments.
     * @param int|null $workspaceId Backend only: the workspace to operate in. `null` defaults to the
     *                              live workspace (0). Ignored for frontend environments.
     */
    public function __construct(
        public readonly ApplicationType $applicationType,
        public readonly ?int $pageId = null,
        public readonly ?int $languageId = null,
        public readonly ?int $backendUserId = null,
        public readonly ?int $workspaceId = null,
    ) {}
}
