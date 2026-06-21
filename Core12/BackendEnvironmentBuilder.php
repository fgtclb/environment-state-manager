<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend environment builder for TYPO3 v12.
 *
 * Assembles a backend environment for a selected page id: a backend PSR-7 request
 * (`applicationType` BE, `normalizedParams`, resolved `site`), a backend user, a language
 * service and a populated context (`backend.user` and `workspace` aspects). This mirrors what
 * the backend middleware chain produces during an HTTP request so backend code (page TSconfig
 * resolution, `BackendUtility` calls, ...) can run inside commands, scheduler tasks or tests.
 *
 * This class lives in the root-level `Core12/` folder and is loaded into the dependency injection
 * container exclusively when the running TYPO3 major version is 12 (see
 * `EXT:environment_state_manager/Configuration/Services.php`, which loads only the `Core{major}/`
 * folder matching `Typo3Version::getMajorVersion()`). The `#[AsAlias]` attribute below binds it to a
 * stable, version-independent service id that {@see EnvironmentBuilderFactory} injects as the
 * backend builder.
 *
 * @internal Concrete, TYPO3 v12 specific implementation of {@see EnvironmentBuilderInterface}. Resolved
 *           through dependency injection — type-hint the interface, not this class. Not covered by
 *           the extension's public-API backward-compatibility promise.
 */
#[AsAlias(id: 'fgtclb.environment_state_manager.backend_environment_builder')]
final class BackendEnvironmentBuilder implements EnvironmentBuilderInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * Builds the backend environment as configured by the given build context.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface
    {
        $workspaceId = $stateBuildContext->workspaceId ?? 0;
        $site = $this->determineSite($stateBuildContext);
        $backendUser = $this->createBackendUser($stateBuildContext, $workspaceId);
        $request = $this->createRequest($site);
        $context = $this->createContext($backendUser, $workspaceId);
        $languageService = $this->languageServiceFactory->createFromUserPreferences($backendUser);

        return (new State())
            ->withRequest($request)
            ->withBackendUserAuthentication($backendUser)
            ->withLanguageService($languageService)
            ->withContext($context)
            ->withAdditionalData('backend', [
                'pageId' => $stateBuildContext->pageId,
                'workspaceId' => $workspaceId,
                'site' => $site,
            ]);
    }

    private function createRequest(SiteInterface $site): ServerRequest
    {
        $request = new ServerRequest(new Uri('/typo3/'), 'GET');
        return $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('site', $site);
    }

    private function createContext(BackendUserAuthentication $backendUser, int $workspaceId): Context
    {
        // Note creating with new here on purpose to have a clean new instance.
        $context = new Context();
        $context->setAspect('backend.user', new UserAspect($backendUser));
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
        return $context;
    }

    private function createBackendUser(StateBuildContext $stateBuildContext, int $workspaceId): BackendUserAuthentication
    {
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        if ($stateBuildContext->backendUserId !== null) {
            $backendUser->setBeUserByUid($stateBuildContext->backendUserId);
            if ((int)($backendUser->user['uid'] ?? 0) > 0) {
                // Load groups, permissions and user TSconfig for the resolved backend user.
                $backendUser->fetchGroupData();
            }
        } else {
            // No backend user requested: use a synthetic in-memory admin so the environment has
            // full access without depending on an existing `be_users` record.
            $backendUser->user = $this->createSyntheticAdminUserRecord($workspaceId);
        }
        $backendUser->workspace = $workspaceId;
        return $backendUser;
    }

    /**
     * @return array<string, mixed>
     */
    private function createSyntheticAdminUserRecord(int $workspaceId): array
    {
        return [
            'uid' => 0,
            'pid' => 0,
            'username' => '_cli_',
            'admin' => 1,
            'usergroup' => '',
            'workspace_id' => $workspaceId,
            'workspace_perms' => 1,
            'disable' => 0,
            'deleted' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'lang' => 'default',
            'realName' => 'Environment State Manager synthetic admin',
            'email' => '',
        ];
    }

    private function determineSite(StateBuildContext $stateBuildContext): SiteInterface
    {
        $pageId = $stateBuildContext->pageId;
        if ($pageId === null || $pageId <= 0) {
            return new NullSite();
        }
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            // A backend environment may legitimately operate on pages outside any site, so fall
            // back to a NullSite instead of failing the build.
            return new NullSite();
        }
    }
}
