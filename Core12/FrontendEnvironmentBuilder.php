<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\Exception\SiteConfigCouldNotBeDetermined;
use FGTCLB\EnvironmentStateManager\ServerEnvironmentVariables;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Environment builder for TYPO3 v12.
 *
 * This class lives in the root-level `Core12/` folder and is loaded into the dependency injection
 * container exclusively when the running TYPO3 major version is 12 (see
 * `EXT:environment_state_manager/Configuration/Services.php`, which loads only the `Core{major}/`
 * folder matching `Typo3Version::getMajorVersion()`). The `#[AsAlias]` attribute below binds it to a
 * stable, version-independent service id that {@see EnvironmentBuilderFactory} injects as the
 * frontend builder.
 *
 * @internal Concrete, TYPO3 v12 specific implementation of {@see EnvironmentBuilderInterface}. Resolved
 *           through dependency injection — type-hint the interface, not this class. Not covered by
 *           the extension's public-API backward-compatibility promise.
 */
#[AsAlias(id: 'fgtclb.environment_state_manager.frontend_environment_builder')]
final class FrontendEnvironmentBuilder implements EnvironmentBuilderInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Builds the environment as configured by the given build context.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface
    {
        $state = new State();
        $site = $this->determineSiteConfig($stateBuildContext);
        $siteLanguage = $this->determineSiteLanguage($stateBuildContext, $site);
        $state = $this->createRequest($stateBuildContext, $state, $site, $siteLanguage);
        $state = $this->createTypoScriptFrontendController($stateBuildContext, $state, $site, $siteLanguage);
        return $state;
    }

    private function createRequest(StateBuildContext $stateBuildContext, State $state, Site $site, SiteLanguage $siteLanguage): State
    {
        $uriLanguage = $siteLanguage->getBase();
        $uriSite = $site->getBase();
        $uri = (new Uri())
            ->withScheme($uriLanguage->getScheme() ?: $uriSite->getScheme() ?: 'http')
            ->withHost($uriLanguage->getHost() ?: $uriSite->getHost() ?: '')
            ->withPort($uriLanguage->getPort() ?? $uriSite->getPort() ?? null)
            ->withPath($uriLanguage->getPath() ?: $uriSite->getPath() ?: '/');
        $serverParams = [
            'HTTP_HOST' => $uri->getHost(),
            'SERVER_NAME' => $uri->getHost(),
            'HTTPS' => ($uri->getScheme() === 'https' ? 'on' : 'off'),
            'SCRIPT_FILENAME' => __FILE__,
            'SCRIPT_NAME' => rtrim($uri->getPath(), '/') . '/',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/' . ltrim($uri->getPath(), '/'),
        ];
        foreach (ServerEnvironmentVariables::NAMES as $var) {
            if (array_key_exists($var, $serverParams)) {
                // Required for the environment, so apply it to $_SERVER.
                $_SERVER[$var] = $serverParams[$var];
                continue;
            }
            // Not part of the environment, so remove it from $_SERVER.
            unset($_SERVER[$var]);
        }
        // This is needed so the IndpEnv cache is flushed properly, ensuring that extension or
        // core calls to `GeneralUtility::getIndpEnv()` return values based on the prepared
        // environment. Ignoring `@internal` here is intentional.
        GeneralUtility::flushInternalRuntimeCaches();
        $request = (new ServerRequest(
            $uri,
            'GET',
            null,
            [],
            $serverParams,
        ))->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request
            ->withAttribute('site', $site)
            ->withAttribute('siteLanguage', $siteLanguage)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        return $state
            ->withRequest($request)
            ->withAdditionalData('_SERVER', $serverParams);
    }

    private function createTypoScriptFrontendController(StateBuildContext $stateBuildContext, State $state, Site $site, SiteLanguage $siteLanguage): State
    {
        $request = $state->request() ?? new ServerRequest();
        // Intentionally instantiated with `new` here to get a clean, fresh instance.
        $context = new Context();
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        $pageId = $this->getNearestAccessiblePage($stateBuildContext->pageId ?? $site->getRootPageId(), $context)
            ?: $site->getRootPageId();
        // Make sure frontend user authentication is present in the request.
        // @todo Consider whether frontend user authentication data could be provided through StateBuildContext.
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);
        $this->unpackFrontendUserConfiguration($frontendUser);
        $request = $request->withAttribute('frontend.user', $frontendUser);
        // Force template parsing via TypoScriptAspect::$forcedTemplateParsing (required to initialize the TypoScript setup).
        $typoScriptAspect = GeneralUtility::makeInstance(TypoScriptAspect::class, true);
        $context->setAspect('typoscript', $typoScriptAspect);
        // Bootstrap the TypoScriptFrontendController.
        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $context,
            $site,
            $siteLanguage,
            new PageArguments(
                pageId: $pageId,
                pageType: '0',
                routeArguments: [],
            ),
            $frontendUser,
        );
        $request = $request->withAttribute('frontend.controller', $controller);
        $controller->determineId($request);
        $controller->calculateLinkVars([]);
        $request = $controller->getFromCache($request);
        $controller->releaseLocks();
        $controller->newCObj($request);
        if (!$controller->sys_page instanceof PageRepository) {
            $controller->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        }
        return $state
            ->withRequest($request)
            ->withTypoScriptFrontendController($controller)
            ->withContext($context);
    }

    /**
     * @param StateBuildContext $buildContext
     * @return Site
     * @throws SiteConfigCouldNotBeDetermined
     */
    private function determineSiteConfig(StateBuildContext $buildContext): Site
    {
        $pageId = $buildContext->pageId;
        if ($pageId === null || $pageId <= 0) {
            $allSiteConfigs = $this->siteFinder->getAllSites();
            if (count($allSiteConfigs) > 1) {
                throw new SiteConfigCouldNotBeDetermined(
                    'No pageId or pageId=0 given and multiple site configuration found.',
                    1762255738,
                );
            }
            if ($allSiteConfigs === []) {
                throw new SiteConfigCouldNotBeDetermined(
                    'No pageId or pageId=0 given and no existing site configuration found.',
                    1762255830,
                );
            }
            return $allSiteConfigs[array_key_first($allSiteConfigs)];
        }
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            throw new SiteConfigCouldNotBeDetermined(
                sprintf(
                    'Could not find SiteConfiguration for pageId=%s',
                    $buildContext->pageId,
                ),
                1762255989,
                $e,
            );
        }
    }

    /**
     * Determines the SiteLanguage to use from the given build context. Falls back to the site's
     * default language when no language is configured.
     */
    private function determineSiteLanguage(StateBuildContext $buildContext, Site $site): SiteLanguage
    {
        if ($buildContext->languageId === null) {
            return $site->getDefaultLanguage();
        }
        return $site->getLanguageById($buildContext->languageId);
    }

    /**
     * Unpacks the serialized frontend user configuration.
     */
    private function unpackFrontendUserConfiguration(FrontendUserAuthentication $frontendUserAuthentication): void
    {
        if (!isset($frontendUserAuthentication->user['uc'])) {
            return;
        }
        $userConfiguration = unserialize($frontendUserAuthentication->user['uc'], ['allowed_classes' => false]);
        if (!is_array($userConfiguration)) {
            return;
        }
        $frontendUserAuthentication->uc = $userConfiguration;
    }

    private function getNearestAccessiblePage(int $pageId, ?Context $context = null): int
    {
        try {
            $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, '', $context)->get();
            foreach ($rootline as $pageRecord) {
                $uid = (int)($pageRecord['uid'] ?? 0);
                $pageDoktype = (int)($pageRecord['doktype'] ?? 0);
                $hidden = (bool)($pageRecord['hidden'] ?? true);
                $isSpacerOrSysfolder = $pageDoktype === PageRepository::DOKTYPE_SPACER || $pageDoktype === PageRepository::DOKTYPE_SYSFOLDER;
                if (!$isSpacerOrSysfolder && !$hidden) {
                    return $uid;
                }
            }
        } catch (RootLineException) {
        }
        return 0;
    }
}
