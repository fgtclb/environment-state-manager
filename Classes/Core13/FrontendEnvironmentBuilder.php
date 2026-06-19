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
 * Environment builder compatible with TYPO3 v13.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:environment_state_manager/Configuration/Services.php` file.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
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
     * Build environment configured by passed $buildContext.
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
                // Wanted for environment, apply it to $_SERVER.
                $_SERVER[$var] = $serverParams[$var];
                continue;
            }
            // not set in for environment, remove it from $_SERVER.
            unset($_SERVER[$var]);
        }
        // This is required to ensure that the IndpEnv cache is cleared properly to ensure
        // that extension or core calls to `GeneralUtility::getIndpEnv()` retrieves values
        // based on the applied/prepared environment. Ignoring `@internal` is done intentionally.
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
        // Note creating with new here on purpose to have a clean new instance.
        $context = new Context();
        // Ensure to have a preview aspect set to the context
        $context->setAspect('frontend.preview', new PreviewAspect());
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        $this->overrideContextData(GeneralUtility::makeInstance(Context::class), $context);
        $pageId = $this->getNearestAccessiblePage($stateBuildContext->pageId ?? $site->getRootPageId(), $context)
            ?: $site->getRootPageId();
        // Ensure frontend user authentication in request
        // @todo Consider if frontend user authentication data may be set through StateBuildContext.
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);
        $this->unpackFrontendUserConfiguration($frontendUser);
        $request = $request->withAttribute('frontend.user', $frontendUser);
        // Prepare other request attributes
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
            // $originalRequest does not contain site ...
            $expressionMatcherVariables,
            $this->typoScriptCache,
        );
        // Note, that we need the full TypoScript setup array, which is required for links created by
        // DatabaseRecordLinkBuilder. This should be kept in mind when TSFE will be removed in v14.
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
     * Determine the SiteLanguage to use based on the state build context, if available. Otherwise default language
     * is returned as language to use.
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
     * Helper method to unpack the serialized frontend user configuration.
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
