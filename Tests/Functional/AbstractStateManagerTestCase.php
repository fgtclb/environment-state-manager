<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\Event\StateApplyEvent;
use FGTCLB\EnvironmentStateManager\Event\StateBackupEvent;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\StateManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shared functional test body for the version-specific state managers.
 *
 * The backup/restore/stack mechanics and the request/backend-user/event expectations are identical
 * across TYPO3 core versions and live here. The few TYPO3 core-version specific concerns - the
 * concrete `State`/`StateManager` classes, the version-specific state a core version keeps in
 * `$GLOBALS` (the TypoScriptFrontendController on TYPO3 v13, nothing on TYPO3 v14), and which
 * context aspect the running core seeds on a backed-up state - are delegated to the abstract hooks
 * the thin `CoreNN` subclasses implement.
 */
abstract class AbstractStateManagerTestCase extends AbstractEnvironmentStateManagerTestCase
{
    /**
     * @return class-string<StateInterface>
     */
    abstract protected function stateClass(): string;

    /**
     * The version-specific extended state interface, or null when the running TYPO3 core version
     * carries no version-specific state (TYPO3 v14 and above).
     *
     * @return class-string|null
     */
    abstract protected function extendedStateInterfaceClass(): ?string;

    abstract protected function createState(
        ?ServerRequestInterface $request = null,
        ?object $versionSpecificState = null,
        ?BackendUserAuthentication $backendUserAuthentication = null,
    ): StateInterface;

    abstract protected function instantiateStateManager(
        EnvironmentBuilderFactoryInterface $environmentBuilderFactory,
    ): StateManagerInterface;

    /**
     * The `$GLOBALS` key holding version-specific state, or null when there is none. TYPO3 v13 keeps
     * the TypoScriptFrontendController in `$GLOBALS['TSFE']`; TYPO3 v14 removed it.
     */
    abstract protected function versionSpecificGlobalKey(): ?string;

    /**
     * Creates the object stored under {@see versionSpecificGlobalKey()}, or null when there is none.
     */
    abstract protected function createVersionSpecificGlobal(): ?object;

    /**
     * Read the version-specific state back from a state instance, or null when there is none.
     */
    abstract protected function readVersionSpecificState(StateInterface $state): ?object;

    /**
     * Assert the context a freshly backed-up state carries on the running TYPO3 core version.
     */
    abstract protected function assertBackedUpStateContext(StateInterface $state): void;

    /**
     * The version-specific global used by the restore test, which may need more setup than a plain
     * mock. Defaults to {@see createVersionSpecificGlobal()}.
     */
    protected function createVersionSpecificGlobalForRestore(): ?object
    {
        return $this->createVersionSpecificGlobal();
    }

    /**
     * Helpers keeping the version-specific global handling out of the shared test bodies.
     */
    final protected function setVersionSpecificGlobal(?object $value): void
    {
        $key = $this->versionSpecificGlobalKey();
        if ($key === null) {
            return;
        }
        if ($value === null) {
            unset($GLOBALS[$key]);
            return;
        }
        $GLOBALS[$key] = $value;
    }

    final protected function assertVersionSpecificGlobalSame(?object $expected): void
    {
        $key = $this->versionSpecificGlobalKey();
        if ($key === null) {
            return;
        }
        $this->assertSame($expected, $GLOBALS[$key] ?? null);
    }

    final protected function assertVersionSpecificGlobalNotSet(): void
    {
        $key = $this->versionSpecificGlobalKey();
        if ($key === null) {
            return;
        }
        $this->assertArrayNotHasKey($key, $GLOBALS);
    }

    final protected function assertStateInstanceOfExtendedInterface(StateInterface $state): void
    {
        $extendedStateInterfaceClass = $this->extendedStateInterfaceClass();
        if ($extendedStateInterfaceClass === null) {
            return;
        }
        $this->assertInstanceOf($extendedStateInterfaceClass, $state);
    }

    final protected function assertVersionSpecificStateSame(StateInterface $state, ?object $expected): void
    {
        if ($this->versionSpecificGlobalKey() === null) {
            return;
        }
        $this->assertSame($expected, $this->readVersionSpecificState($state));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $this->setVersionSpecificGlobal(null);
        parent::tearDown();
    }

    #[Test]
    public function backupAddsExpectedStateOnInternalStack(): void
    {
        $dispatchedBackupEvents = [];
        $this->interceptBackupEvents($dispatchedBackupEvents);
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $versionSpecificGlobal = $this->createVersionSpecificGlobal();
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['TYPO3_REQUEST'] = $requestMock;
        $this->setVersionSpecificGlobal($versionSpecificGlobal);
        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;
        $stateManager = $this->createStateManager();
        // before
        $this->assertCount(0, $this->readStack($stateManager));
        $this->assertSame($requestMock, $GLOBALS['TYPO3_REQUEST']);
        $this->assertVersionSpecificGlobalSame($versionSpecificGlobal);
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER']);
        // execution
        $stateManager->backup();
        // after
        $stack = $this->readStack($stateManager);
        $this->assertCount(1, $stack);
        $this->assertArrayHasKey(0, $stack);
        $firstState = $stack[0];
        $this->assertInstanceOf(StateInterface::class, $firstState);
        $this->assertStateInstanceOfExtendedInterface($firstState);
        $this->assertInstanceOf($this->stateClass(), $firstState);
        $this->assertIsObject($firstState->request());
        $this->assertIsObject($firstState->context());
        $this->assertBackedUpStateContext($firstState);
        $this->assertIsObject($firstState->backendUserAuthentication());
        $this->assertSame($requestMock, $firstState->request());
        $this->assertVersionSpecificStateSame($firstState, $versionSpecificGlobal);
        $this->assertSame($backendUserAuthenticationMock, $firstState->backendUserAuthentication());
        // assert event count
        $this->assertCount(1, $dispatchedBackupEvents);
    }

    #[Test]
    public function backupAddsExpectedStateAsSecondItemOnInternalStack(): void
    {
        $dispatchedBackupEvents = [];
        $this->interceptBackupEvents($dispatchedBackupEvents);
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $versionSpecificGlobal = $this->createVersionSpecificGlobal();
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $stateManager = $this->createStateManager();
        // before
        $this->assertCount(0, $this->readStack($stateManager));
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertVersionSpecificGlobalSame(null);
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        // backup 1
        $stateManager->backup();
        // change environment
        $GLOBALS['TYPO3_REQUEST'] = $requestMock;
        $this->setVersionSpecificGlobal($versionSpecificGlobal);
        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;
        $this->assertSame($requestMock, $GLOBALS['TYPO3_REQUEST']);
        $this->assertVersionSpecificGlobalSame($versionSpecificGlobal);
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER']);
        // backup 2
        $stateManager->backup();
        // after
        $stack = $this->readStack($stateManager);
        $this->assertCount(2, $stack);
        $this->assertArrayHasKey(0, $stack);
        $this->assertArrayHasKey(1, $stack);
        $firstState = $stack[0];
        $this->assertInstanceOf(StateInterface::class, $firstState);
        $this->assertStateInstanceOfExtendedInterface($firstState);
        $this->assertInstanceOf($this->stateClass(), $firstState);
        $this->assertNull($firstState->request());
        $this->assertVersionSpecificStateSame($firstState, null);
        $this->assertNull($firstState->backendUserAuthentication());
        $secondState = $stack[1];
        $this->assertInstanceOf(StateInterface::class, $secondState);
        $this->assertStateInstanceOfExtendedInterface($secondState);
        $this->assertInstanceOf($this->stateClass(), $secondState);
        $this->assertSame($requestMock, $secondState->request());
        $this->assertVersionSpecificStateSame($secondState, $versionSpecificGlobal);
        $this->assertSame($backendUserAuthenticationMock, $secondState->backendUserAuthentication());
        // assert event count
        $this->assertCount(2, $dispatchedBackupEvents);
    }

    #[Test]
    public function restoreSetsExpectedStateToEnvironment(): void
    {
        $dispatchedApplyEvents = [];
        $this->interceptApplyEvents($dispatchedApplyEvents);
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $versionSpecificGlobal = $this->createVersionSpecificGlobalForRestore();
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $expectedState = $this->createState(
            request: $request,
            versionSpecificState: $versionSpecificGlobal,
            backendUserAuthentication: $backendUserAuthenticationMock,
        );
        $stateManager = $this->createStateManager();
        $this->setStack($stateManager, [0 => $expectedState]);
        // before
        $context = GeneralUtility::makeInstance(Context::class);
        $this->assertCount(1, $this->readStack($stateManager));
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertVersionSpecificGlobalSame(null);
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        // Applying a frontend-request state seeds a fresh preview aspect (version-agnostic, handled in
        // the shared helper trait), so it must not be present yet.
        $this->assertFalse($context->hasAspect('frontend.preview'));
        // restore
        $stateManager->restore();
        // after
        // @phpstan-ignore-next-line PHPStan cannot track that restore() repopulated the superglobal.
        $this->assertSame($request, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertVersionSpecificGlobalSame($versionSpecificGlobal);
        // @phpstan-ignore-next-line PHPStan cannot track that restore() repopulated the superglobal.
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER'] ?? null);
        $this->assertTrue($context->hasAspect('frontend.preview'));
        $this->assertCount(0, $this->readStack($stateManager));
        // restore on empty stack resets environment to empty state - expected !
        $stateManager->restore();
        // @phpstan-ignore-next-line PHPStan cannot track that restore() cleared the superglobal.
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertVersionSpecificGlobalSame(null);
        // @phpstan-ignore-next-line PHPStan cannot track that restore() cleared the superglobal.
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        $this->assertFalse($context->hasAspect('frontend.preview'));
        // assert event count
        $this->assertCount(2, $dispatchedApplyEvents);
    }

    #[Test]
    public function resetClearsEnvironmentAndDispatchesApplyEvent(): void
    {
        $dispatchedApplyEvents = [];
        $this->interceptApplyEvents($dispatchedApplyEvents);
        $GLOBALS['TYPO3_REQUEST'] = $this->createMock(ServerRequestInterface::class);
        $this->setVersionSpecificGlobal($this->createVersionSpecificGlobal());
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $stateManager = $this->createStateManager();
        // execution
        $stateManager->reset();
        // after - the empty state cleared every managed global ...
        $this->assertArrayNotHasKey('TYPO3_REQUEST', $GLOBALS);
        $this->assertVersionSpecificGlobalNotSet();
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS);
        // ... and an apply event was dispatched.
        $this->assertCount(1, $dispatchedApplyEvents);
    }

    /**
     * @param list<StateBackupEvent> $collector
     */
    private function interceptBackupEvents(array &$collector): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'state-backup-event-interceptor',
            static function (StateBackupEvent $event) use (&$collector): void {
                $collector[] = $event;
            }
        );
        $container->get(ListenerProvider::class)->addListener(StateBackupEvent::class, 'state-backup-event-interceptor');
    }

    /**
     * @param list<StateApplyEvent> $collector
     */
    private function interceptApplyEvents(array &$collector): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'state-apply-event-interceptor',
            static function (StateApplyEvent $event) use (&$collector): void {
                $collector[] = $event;
            }
        );
        $container->get(ListenerProvider::class)->addListener(StateApplyEvent::class, 'state-apply-event-interceptor');
    }

    protected function createEnvironmentBuilderFactoryMock(
        EnvironmentBuilderInterface $frontendEnvironmentBuilder,
    ): MockObject&EnvironmentBuilderFactoryInterface {
        $environmentBuilderFactoryMock = $this->createMock(EnvironmentBuilderFactoryInterface::class);
        $environmentBuilderFactoryMock
            ->method('create')
            ->willReturnCallback(function (StateBuildContext $stateBuildContext) use ($frontendEnvironmentBuilder): EnvironmentBuilderInterface {
                if ($stateBuildContext->applicationType === ApplicationType::BACKEND) {
                    throw new \RuntimeException(
                        'Only frontend applicationType implemented for mocked environmentFactoryMock',
                        1762298777,
                    );
                }
                return $frontendEnvironmentBuilder;
            });

        return $environmentBuilderFactoryMock;
    }

    protected function createEnvironmentBuilderMock(): MockObject&EnvironmentBuilderInterface
    {
        return $this->createMock(EnvironmentBuilderInterface::class);
    }

    protected function createStateManager(
        ?EnvironmentBuilderFactoryInterface $environmentBuilderFactory = null,
    ): StateManagerInterface {
        $environmentBuilderFactory ??= $this->get(EnvironmentBuilderFactoryInterface::class);
        return $this->instantiateStateManager($environmentBuilderFactory);
    }

    /**
     * Reads the manager's internal snapshot stack, keeping the reflection in one place.
     *
     * @return array<int, StateInterface>
     */
    protected function readStack(StateManagerInterface $stateManager): array
    {
        /** @var array<int, StateInterface> $stack */
        $stack = (new \ReflectionObject($stateManager))->getProperty('stack')->getValue($stateManager);
        return $stack;
    }

    /**
     * @param array<int, StateInterface> $stack
     */
    protected function setStack(StateManagerInterface $stateManager, array $stack): void
    {
        (new \ReflectionObject($stateManager))->getProperty('stack')->setValue($stateManager, $stack);
    }
}
