<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use FGTCLB\EnvironmentStateManager\Event\StateApplyEvent;
use FGTCLB\EnvironmentStateManager\Event\StateBackupEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This trait provides internal helper methods built around the {@see StateInterface} getters, so
 * {@see StateManagerInterface} implementations can reuse them for the base interface and avoid code duplication.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
trait StateManagerRootStateInterfaceHelperMethodsTrait
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
        /** @var ContentObjectRenderer|null $contentObjectRenderer */
        $contentObjectRenderer = null;
        if ($state->typoScriptFrontendController() !== null) {
            $GLOBALS['TSFE'] = $state->typoScriptFrontendController();
            // A fresh ServerRequest is only passed here to keep PHPStan happy across multiple core
            // versions; a request should always exist when the state snapshot contains a
            // TypoScriptFrontendController.
            $GLOBALS['TSFE']->newCObj($state->request() ?? new ServerRequest());
            $contentObjectRenderer = $GLOBALS['TSFE']->cObj;
        } else {
            unset($GLOBALS['TSFE']);
        }
        if ($state->backendUserAuthentication() !== null) {
            $GLOBALS['BE_USER'] = $state->backendUserAuthentication();
        } else {
            unset($GLOBALS['BE_USER']);
        }
        // Apply the super globals.
        $superGlobals = $state->additionalData('_SERVER');
        foreach ($this->SERVER_SUPERGLOBAL_VARS as $var) {
            if (!is_array($superGlobals)) {
                if (!is_array($_SERVER)) {
                    // No super globals wanted for the environment and the super global does not exist, so skip it.
                    continue;
                }
                // No super globals wanted for the environment but the super global exists, so remove the unset variable.
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
        if ($contentObjectRenderer !== null) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            if (method_exists($configurationManager, 'setRequest') && $state->request() !== null) {
                // TYPO3 v13
                $configurationManager->setRequest($state->request());
            }
            if (method_exists($configurationManager, 'setContentObject')) {
                // TYPO3 v12
                $configurationManager->setContentObject($contentObjectRenderer);
            }
        }
    }

    final protected function backupStateInterface(StateInterface $state): StateInterface
    {
        $context = clone GeneralUtility::makeInstance(Context::class);
        /** @var ServerRequestInterface|null $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        /** @var TypoScriptFrontendController|null $typoScriptFrontendController */
        $typoScriptFrontendController = $GLOBALS['TSFE'] ?? null;
        $applicationType = $request !== null && $request->getAttribute('applicationType') ?: null;
        $pageRenderer = $request !== null && $applicationType !== null ? GeneralUtility::makeInstance(PageRenderer::class) : null;
        $superGlobals = [];
        if (is_array($_SERVER)) {
            foreach ($this->SERVER_SUPERGLOBAL_VARS as $var) {
                if (array_key_exists($var, $_SERVER)) {
                    $superGlobals['_SERVER'] ??= [];
                    $superGlobals['_SERVER'][$var] = $_SERVER[$var];
                }
            }
        }
        return $state
            ->withContext($context)
            ->withRequest($request)
            ->withTypoScriptFrontendController($typoScriptFrontendController)
            ->withBackendUserAuthentication($GLOBALS['BE_USER'] ?? null)
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
