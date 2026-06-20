<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core13;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\Exception\SiteConfigCouldNotBeDetermined;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;

/**
 * Environment builder for TYPO3 v13.
 *
 * The `#[Exclude]` attribute is set on purpose. It keeps this class from being compiled
 * early into the dependency injection container, which would otherwise trigger missing-class
 * and similar errors for unrelated TYPO3 versions. The TYPO3 version-aware configuration is
 * handled and re-enabled in the `EXT:environment_state_manager/Configuration/Services.php` file.
 *
 * @internal Concrete, TYPO3 v13 specific implementation of {@see EnvironmentBuilderInterface}. Resolved
 *           through dependency injection — type-hint the interface, not this class. Not covered by
 *           the extension's public-API backward-compatibility promise.
 */
#[Exclude]
final class FrontendEnvironmentBuilder implements EnvironmentBuilderInterface
{
    /** @var string[] */
    private $SERVER_SUPERGLOBAL_VARS = [
        'HTTP_HOST',
        'SERVER_NAME',
        'HTTPS',
        'SCRIPT_FILENAME',
        'SCRIPT_NAME',
        'REMOTE_ADDR',
        'REQUEST_URI',
    ];

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly PageInformationFactory $pageInformationFactory,
        private readonly FrontendTypoScriptFactory $frontendTypoScriptFactory,
        #[Autowire(service: 'cache.typoscript')]
        private readonly PhpFrontend $typoScriptCache,
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
        foreach ($this->SERVER_SUPERGLOBAL_VARS as $var) {
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
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('site', $site)
            ->withAttribute('language', $siteLanguage)
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
        // Make sure a preview aspect is set on the context.
        $context->setAspect('frontend.preview', new PreviewAspect());
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        $this->overrideContextData(GeneralUtility::makeInstance(Context::class), $context);
        $pageId = $this->getNearestAccessiblePage($stateBuildContext->pageId ?? $site->getRootPageId(), $context)
            ?: $site->getRootPageId();
        // Make sure frontend user authentication is present in the request.
        // @todo Consider whether frontend user authentication data could be provided through StateBuildContext.
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);
        $this->unpackFrontendUserConfiguration($frontendUser);
        $request = $request->withAttribute('frontend.user', $frontendUser);
        // Prepare the remaining request attributes.
        $cacheInstruction = new CacheInstruction();
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        $pageArguments = new PageArguments($pageId, '0', []);
        $request = $request->withAttribute('routing', $pageArguments);
        $pageInformation = $this->pageInformationFactory->create($request);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        // Bootstrap TypoScriptFrontendController
        $controller = GeneralUtility::makeInstance(TypoScriptFrontendController::class);
        $request = $request->withAttribute('frontend.controller', $controller);
        $expressionMatcherVariables = $this->getExpressionMatcherVariables($site, $request, $controller);
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $pageInformation->getSysTemplateRows(),
            // Note: $originalRequest does not contain the site ...
            $expressionMatcherVariables,
            $this->typoScriptCache,
        );
        // Note that we need the full TypoScript setup array, since it is required for links created
        // by DatabaseRecordLinkBuilder. Keep this in mind once TSFE is removed in v14.
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(
            true,
            $frontendTypoScript,
            $site,
            $pageInformation->getSysTemplateRows(),
            $expressionMatcherVariables,
            '0',
            $this->typoScriptCache,
            null
        );
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $controller->initializePageRenderer($request);
        $controller->newCObj($request);
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
     * @return array{
     *     request: ServerRequestInterface,
     *     pageId: int,
     *     page: array<string, mixed>,
     *     fullRootLine: array<int, array<string, mixed>>,
     *     localRootLine: array<int, array<string, mixed>>,
     *     site: SiteInterface,
     *     siteLanguage: SiteLanguage,
     *     tsfe: TypoScriptFrontendController,
     * }
     */
    private function getExpressionMatcherVariables(SiteInterface $site, ServerRequestInterface $request, TypoScriptFrontendController $controller): array
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $topDownRootLine = $pageInformation->getRootLine();
        $localRootline = $pageInformation->getLocalRootLine();
        ksort($topDownRootLine);
        return [
            'request' => $request,
            'pageId' => $pageInformation->getId(),
            'page' => $pageInformation->getPageRecord(),
            'fullRootLine' => $topDownRootLine,
            'localRootLine' => $localRootline,
            'site' => $site,
            'siteLanguage' => $request->getAttribute('language'),
            'tsfe' => $controller,
        ];
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

    final protected function overrideContextData(Context $context, Context $overrideContext): void
    {
        $propertyAccessor = new \ReflectionProperty(Context::class, 'aspects');
        $propertyAccessor->setValue($context, $propertyAccessor->getValue($overrideContext));
    }

    final protected function resetContextData(Context $context): void
    {
        $propertyAccessor = new \ReflectionProperty(Context::class, 'aspects');
        $propertyAccessor->setValue($context, [
            'date' => new DateTimeAspect(DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME'])),
            'visibility' => new VisibilityAspect(),
            'backend.user' => new UserAspect(),
            'frontend.user' => new UserAspect(),
            'workspace' => new WorkspaceAspect(),
            'language' => new LanguageAspect(),
        ]);
    }
}
