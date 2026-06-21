<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerExecuteMethodTrait;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use FGTCLB\EnvironmentStateManager\StateManagerRootStateInterfaceHelperMethodsTrait;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Default implementation of {@see StateManagerInterface} for TYPO3 v12.
 *
 * This class lives in the root-level `Core12/` folder and is loaded into the dependency injection
 * container exclusively when the running TYPO3 major version is 12. The version-aware configuration
 * in `EXT:environment_state_manager/Configuration/Services.php` only loads the `Core{major}/` folder
 * matching `Typo3Version::getMajorVersion()`. The `#[AsAlias]` attribute below publishes this class
 * as the version-agnostic {@see StateManagerInterface} service.
 *
 * @internal Concrete, TYPO3 v12 specific implementation of {@see StateManagerInterface}. Resolved
 *           through dependency injection — type-hint the interface, not this class. Not covered by
 *           the extension's public-API backward-compatibility promise.
 */
#[AsAlias(id: StateManagerInterface::class, public: true)]
final class StateManager implements StateManagerInterface
{
    use StateManagerRootStateInterfaceHelperMethodsTrait;
    use StateManagerExecuteMethodTrait;

    /**
     * @var State[]
     */
    private array $stack = [];

    public function __construct(
        private readonly EnvironmentBuilderFactoryInterface $environmentBuilderFactory,
    ) {}

    /**
     * Creates a backup of the current environment and pushes it onto the snapshot stack.
     */
    public function backup(): void
    {
        $state = $this->backupStateInterface(new State());
        if ($state instanceof ExtendedStateInterface) {
            // The TypoScriptFrontendController is TYPO3 core-version specific state (deprecated in
            // v13, removed in v14) and is therefore captured here, on the extended state, rather than
            // in the version-agnostic backup helper.
            /** @var TypoScriptFrontendController|null $typoScriptFrontendController */
            $typoScriptFrontendController = $GLOBALS['TSFE'] ?? null;
            $state = $state->withTypoScriptFrontendController($typoScriptFrontendController);
        }
        $state = $this->dispatchStateBackupEvent($state);
        /** @var State $state */
        array_push($this->stack, $state);
    }

    /**
     * Reset the environment to an empty state.
     *
     * **Be aware** that this method neither backs up nor restores the current environment.
     */
    public function reset(): void
    {
        $this->apply(new State());
    }

    /**
     * Restore the last environment and remove it from the snapshot stack.
     */
    public function restore(): void
    {
        /** @var State $state */
        $state = array_pop($this->stack) ?? new State();
        $this->apply($state);
    }

    /**
     * Creates a state for `$pageId` and populates the environment with it,
     * returning the created state as {@see StateInterface}.
     *
     * **Be aware** that this method changes the environment without creating a backup
     * of it or restoring it. For snapshot handling, see the following methods:
     *
     * - {@see StateManagerInterface::backup()}
     * - {@see StateManagerInterface::restore()}
     *
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function bootstrap(StateBuildContext $stateBuildContext): StateInterface
    {
        $state = $this->environmentBuilderFactory->create($stateBuildContext)->build($stateBuildContext);
        if (!in_array(ExtendedStateInterface::class, class_implements($state), true)) {
            throw new \RuntimeException(
                sprintf(
                    'Class "%s" does not implement extended interface "%s".',
                    $state::class,
                    ExtendedStateInterface::class,
                ),
                1762264455,
            );
        }
        $this->apply($state);
        return $state;
    }

    /**
     * Applies the given state to the environment.
     *
     * **Be aware** that this method changes the environment without creating a backup
     * of it or restoring it. See {@see StateManagerInterface::backup()} and
     * {@see StateManagerInterface::restore()} for snapshot handling.
     */
    public function apply(StateInterface $state): void
    {
        $this->applyStateInterface($state);
        if ($state instanceof ExtendedStateInterface) {
            // The TypoScriptFrontendController is TYPO3 core-version specific state (deprecated in
            // v13, removed in v14) and is therefore applied here, on the extended state, rather than
            // in the version-agnostic apply helper.
            $typoScriptFrontendController = $state->typoScriptFrontendController();
            if ($typoScriptFrontendController !== null) {
                $GLOBALS['TSFE'] = $typoScriptFrontendController;
                // A fresh ServerRequest is only passed to satisfy the signature; a request should
                // always exist when the snapshot carries a TypoScriptFrontendController.
                $typoScriptFrontendController->newCObj($state->request() ?? new ServerRequest());
                // TYPO3 v12: wire the ContentObjectRenderer into the Extbase ConfigurationManager.
                /** @var ContentObjectRenderer|null $contentObjectRenderer */
                $contentObjectRenderer = $typoScriptFrontendController->cObj;
                if ($contentObjectRenderer !== null) {
                    GeneralUtility::makeInstance(ConfigurationManagerInterface::class)
                        ->setContentObject($contentObjectRenderer);
                }
            } else {
                unset($GLOBALS['TSFE']);
            }
        }
        $this->dispatchStateApplyEvent($state);
    }
}
