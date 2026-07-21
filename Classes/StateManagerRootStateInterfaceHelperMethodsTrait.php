<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Event\StateApplyEvent;
use FGTCLB\EnvironmentStateManager\Event\StateBackupEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;

/**
 * This trait provides internal helper methods built around the {@see StateInterface} getters, so
 * {@see StateManagerInterface} implementations can reuse them for the base interface and avoid code duplication.
 *
 * @internal Internal implementation detail of the shipped state managers; not part of the public API.
 */
trait StateManagerRootStateInterfaceHelperMethodsTrait
{
    final protected function applyStateInterface(StateInterface $state): void
    {
        if (!$this instanceof StateManagerInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Trait "%s" must be only used on classes implementing "%s", provided "%s" does not.',
                    StateManagerRootStateInterfaceHelperMethodsTrait::class,
                    StateManagerInterface::class,
                    static::class,
                ),
                1762340764,
            );
        }
        $contextToSet = $state->context();
        if ($contextToSet === null) {
            $contextToSet = new Context();
            if ($state->request() !== null && ApplicationType::fromRequest($state->request())->isFrontend()) {
                $contextToSet->setAspect('frontend.preview', new PreviewAspect());
            }
        }
        $this->overrideContextData(
            // Operate on the real singleton instance.
            GeneralUtility::makeInstance(Context::class),
            $contextToSet,
        );
        if ($state->request() !== null) {
            $GLOBALS['TYPO3_REQUEST'] = $state->request();
        } else {
            unset($GLOBALS['TYPO3_REQUEST']);
        }
        // The TypoScriptFrontendController is TYPO3 core-version specific (deprecated in v13, removed
        // in v14) and is therefore applied by the version-specific state managers through the
        // `ExtendedStateInterface` hook, not here.
        if ($state->backendUserAuthentication() !== null) {
            $GLOBALS['BE_USER'] = $state->backendUserAuthentication();
        } else {
            unset($GLOBALS['BE_USER']);
        }
        if ($state->languageService() !== null) {
            $GLOBALS['LANG'] = $state->languageService();
        } else {
            unset($GLOBALS['LANG']);
        }
        // Apply the super globals.
        $superGlobals = $state->additionalData('_SERVER');
        foreach (ServerEnvironmentVariables::NAMES as $var) {
            if (!is_array($superGlobals)) {
                // No super globals wanted for the environment, so remove the unset variable.
                unset($_SERVER[$var]);
                continue;
            }
            if (array_key_exists($var, $superGlobals)) {
                // Required for the environment, so apply it to $_SERVER.
                $_SERVER[$var] = $superGlobals[$var];
                continue;
            }
            // Not part of the environment, so remove it from $_SERVER.
            unset($_SERVER[$var]);
        }
        // This is needed so the IndpEnv cache is flushed properly, ensuring that extension or
        // core calls to `GeneralUtility::getIndpEnv()` return values based on the prepared
        // environment. Ignoring `@internal` here is intentional.
        GeneralUtility::flushInternalRuntimeCaches();
        // Restore the saved PageRenderer, or at least clean it up.
        $instances = GeneralUtility::getSingletonInstances();
        unset($instances[PageRenderer::class]);
        GeneralUtility::resetSingletonInstances($instances);
        if ($state->pageRenderer() !== null) {
            GeneralUtility::setSingletonInstance(PageRenderer::class, $state->pageRenderer());
        }
        // Provide the active request to the Extbase ConfigurationManager. This only depends on the
        // request, not on a ContentObjectRenderer, so it runs whenever a request exists (frontend
        // and backend). The ContentObjectRenderer wiring is bound to the TypoScriptFrontendController
        // and therefore handled by the version-specific state managers through the
        // `ExtendedStateInterface` hook.
        if ($state->request() !== null) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            $configurationManager->setRequest($state->request());
        }
    }

    final protected function backupStateInterface(StateInterface $state): StateInterface
    {
        $context = clone GeneralUtility::makeInstance(Context::class);
        /** @var ServerRequestInterface|null $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $applicationType = $request !== null && $request->getAttribute('applicationType') ?: null;
        $pageRenderer = $request !== null && $applicationType !== null ? GeneralUtility::makeInstance(PageRenderer::class) : null;
        $superGlobals = [];
        foreach (ServerEnvironmentVariables::NAMES as $var) {
            if (array_key_exists($var, $_SERVER)) {
                $superGlobals['_SERVER'] ??= [];
                $superGlobals['_SERVER'][$var] = $_SERVER[$var];
            }
        }
        /** @var LanguageService|null $languageService */
        $languageService = $GLOBALS['LANG'] ?? null;
        // The TypoScriptFrontendController is read from $GLOBALS by the version-specific state
        // managers through the `ExtendedStateInterface` hook, as it is TYPO3 core-version specific
        // state (deprecated in v13, removed in v14).
        return $state
            ->withContext($context)
            ->withRequest($request)
            ->withBackendUserAuthentication($GLOBALS['BE_USER'] ?? null)
            ->withLanguageService($languageService)
            ->withPageRenderer($pageRenderer)
            ->withAdditionalData('_SERVER', $superGlobals);
    }

    final protected function dispatchStateApplyEvent(StateInterface $state): void
    {
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new StateApplyEvent($state));
    }

    /**
     * @param StateInterface $state
     * @return StateInterface
     */
    final protected function dispatchStateBackupEvent(StateInterface $state): StateInterface
    {
        $event = new StateBackupEvent($state);
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        /** @var StateBackupEvent $event */
        return $event->getState();
    }

    final protected function overrideContextData(Context $context, Context $overrideContext): void
    {
        $propertyAccessor = new \ReflectionProperty(Context::class, 'aspects');
        $propertyAccessor->setValue($context, $propertyAccessor->getValue($overrideContext));
    }
}
