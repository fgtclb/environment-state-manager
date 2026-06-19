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
 * This trait provides internal methods for {@see StateInterface} provided getter methods to use them
 * in {@see StateManagerInterface} implementation for the base interface and reduce code duplication.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
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
            // Operate on real singleton instance.
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
            // New ServerRequest request is only applied to keep PHPStan for multiple core versions
            // happy, request should always exists in case a TypoScriptFrontendController exists in
            // state snapshot.
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
        // apply super globals
        $superGlobals = $state->additionalData('_SERVER');
        foreach ($this->SERVER_SUPERGLOBAL_VARS as $var) {
            if (!is_array($superGlobals)) {
                if (!is_array($_SERVER)) {
                    // No superglobals wanted for environment and super global does not exist, simply skip it.
                    continue;
                }
                // No superglobals wanted for environment but super global exists, ensure to remove not set variable.
                unset($_SERVER[$var]);
                continue;
            }
            if (array_key_exists($var, $superGlobals)) {
                // Wanted for environment, apply it to $_SERVER.
                $_SERVER[$var] = $superGlobals[$var];
                continue;
            }
            // not set in for environment, remove it from $_SERVER.
            unset($_SERVER[$var]);
        }
        // This is required to ensure that the IndpEnv cache is cleared properly to ensure
        // that extension or core calls to `GeneralUtility::getIndpEnv()` retrieves values
        // based on the applied/prepared environment. Ignoring `@internal` is done intentionally.
        GeneralUtility::flushInternalRuntimeCaches();
        // Restore safed PageRender or clean it up at least.
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
