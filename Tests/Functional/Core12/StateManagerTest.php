<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core12;

use FGTCLB\EnvironmentStateManager\Core12\ExtendedStateInterface;
use FGTCLB\EnvironmentStateManager\Core12\State;
use FGTCLB\EnvironmentStateManager\Core12\StateManager;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\Event\StateApplyEvent;
use FGTCLB\EnvironmentStateManager\Event\StateBackupEvent;
use FGTCLB\EnvironmentStateManager\StateInterface;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractEnvironmentStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class StateManagerTest extends AbstractEnvironmentStateManagerTestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $GLOBALS['TSFE'],
        );
        parent::tearDown();
    }

    #[Group('not-core-13')]
    #[Test]
    public function backupAddsExpectedStateOnInternalStack(): void
    {
        $dispatchedBackupEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'state-backup-event-interceptor',
            static function (StateBackupEvent $event) use (&$dispatchedBackupEvents): void {
                $dispatchedBackupEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(StateBackupEvent::class, 'state-backup-event-interceptor');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $typoScriptFrontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['TYPO3_REQUEST'] = $requestMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMock;
        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;
        $environmentBuilderMock = $this->createEnvironmentBuilderMock();
        $environmentBuilderFactoryMock = $this->createEnvironmentBuilderFactoryMock($environmentBuilderMock);
        $stateManager = $this->createStateManager($environmentBuilderFactoryMock);
        $stateManagerReflection = new \ReflectionObject($stateManager);
        $stackPropertyReflection = $stateManagerReflection->getProperty('stack');
        // before
        $this->assertIsObject($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertIsObject($GLOBALS['TSFE'] ?? null);
        $this->assertIsObject($GLOBALS['BE_USER'] ?? null);
        $this->assertCount(0, $stackPropertyReflection->getValue($stateManager));
        $this->assertSame($requestMock, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertSame($typoScriptFrontendControllerMock, $GLOBALS['TSFE'] ?? null);
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER'] ?? null);
        // execution
        $stateManager->backup();
        // after
        $this->assertCount(1, $stackPropertyReflection->getValue($stateManager));
        $stack = $stackPropertyReflection->getValue($stateManager);
        $this->assertCount(1, $stack);
        $this->assertArrayHasKey(0, $stack);
        $firstState = $stack[0] ?? null;
        $this->assertInstanceOf(StateInterface::class, $firstState);
        $this->assertInstanceOf(ExtendedStateInterface::class, $firstState);
        $this->assertInstanceOf(State::class, $firstState);
        $this->assertIsObject($firstState->request());
        $this->assertIsObject($firstState->typoScriptFrontendController());
        $this->assertIsObject($firstState->context());
        $this->assertTrue($firstState->context()->hasAspect('typoscript'));
        $this->assertIsObject($firstState->backendUserAuthentication());
        $this->assertSame($requestMock, $firstState->request());
        $this->assertSame($typoScriptFrontendControllerMock, $firstState->typoScriptFrontendController());
        $this->assertSame($backendUserAuthenticationMock, $firstState->backendUserAuthentication());
        // assert event count
        $this->assertCount(1, $dispatchedBackupEvents);
    }

    #[Group('not-core-13')]
    #[Test]
    public function backupAddsExpectedStateAsSecondItemOnInternalStack(): void
    {
        $dispatchedBackupEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'state-backup-event-interceptor',
            static function (StateBackupEvent $event) use (&$dispatchedBackupEvents): void {
                $dispatchedBackupEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(StateBackupEvent::class, 'state-backup-event-interceptor');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $typoScriptFrontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $environmentBuilderMock = $this->createEnvironmentBuilderMock();
        $environmentBuilderFactoryMock = $this->createEnvironmentBuilderFactoryMock($environmentBuilderMock);
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $stateManager = $this->createStateManager($environmentBuilderFactoryMock);
        $stateManagerReflection = new \ReflectionObject($stateManager);
        $stackPropertyReflection = $stateManagerReflection->getProperty('stack');
        // before
        $this->assertCount(0, $stackPropertyReflection->getValue($stateManager));
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertNull($GLOBALS['TSFE'] ?? null);
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        // backup 1
        $stateManager->backup();
        // change environment
        $GLOBALS['TYPO3_REQUEST'] = $requestMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMock;
        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;
        $this->assertSame($requestMock, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertSame($typoScriptFrontendControllerMock, $GLOBALS['TSFE'] ?? null);
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER'] ?? null);
        // backup 2
        $stateManager->backup();
        // after
        $this->assertCount(2, $stackPropertyReflection->getValue($stateManager));
        $stack = $stackPropertyReflection->getValue($stateManager);
        $this->assertCount(2, $stack);
        $this->assertArrayHasKey(0, $stack);
        $this->assertArrayHasKey(1, $stack);
        $firstState = $stack[0] ?? null;
        $this->assertInstanceOf(StateInterface::class, $firstState);
        $this->assertInstanceOf(ExtendedStateInterface::class, $firstState);
        $this->assertInstanceOf(State::class, $firstState);
        $this->assertNull($firstState->request());
        $this->assertNull($firstState->typoScriptFrontendController());
        $this->assertNull($firstState->backendUserAuthentication());
        $secondState = $stack[1] ?? null;
        $this->assertInstanceOf(StateInterface::class, $secondState);
        $this->assertInstanceOf(ExtendedStateInterface::class, $secondState);
        $this->assertInstanceOf(State::class, $secondState);
        $this->assertSame($requestMock, $secondState->request());
        $this->assertSame($typoScriptFrontendControllerMock, $secondState->typoScriptFrontendController());
        $this->assertSame($backendUserAuthenticationMock, $secondState->backendUserAuthentication());
        // assert event count
        $this->assertCount(2, $dispatchedBackupEvents);
    }

    #[Group('not-core-13')]
    #[Test]
    public function restoreSetsExpectedStateToEnvironment(): void
    {
        $dispatchedApplyEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'state-apply-event-interceptor',
            static function (StateApplyEvent $event) use (&$dispatchedApplyEvents): void {
                $dispatchedApplyEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(StateApplyEvent::class, 'state-apply-event-interceptor');
        $typoScriptAspectMock = $this->createMock(TypoScriptAspect::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $typoScriptFrontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $typoScriptFrontendControllerMock);
        $typoScriptFrontendControllerMock->cObj = $cObj;
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $environmentBuilderMock = $this->createEnvironmentBuilderMock();
        $environmentBuilderFactoryMock = $this->createEnvironmentBuilderFactoryMock($environmentBuilderMock);
        $expectedState = new State(
            request: $request,
            typoScriptFrontendController: $typoScriptFrontendControllerMock,
            backendUserAuthentication: $backendUserAuthenticationMock,
        );
        $stateManager = $this->createStateManager(environmentBuilderFactory: $environmentBuilderFactoryMock);
        $stateManagerReflection = new \ReflectionObject($stateManager);
        $stackPropertyReflection = $stateManagerReflection->getProperty('stack');
        $stackPropertyReflection->setValue($stateManager, [0 => $expectedState]);
        // before
        $context = GeneralUtility::makeInstance(Context::class);
        $this->assertCount(1, $stackPropertyReflection->getValue($stateManager));
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertNull($GLOBALS['TSFE'] ?? null);
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        $this->assertNotSame($typoScriptAspectMock, $context->getAspect('typoscript'));
        // restore
        $stateManager->restore();
        // after
        // @phpstan-ignore-next-line Make PHPStan happy
        $this->assertSame($request, $GLOBALS['TYPO3_REQUEST'] ?? null);
        // @phpstan-ignore-next-line Make PHPStan happy
        $this->assertSame($typoScriptFrontendControllerMock, $GLOBALS['TSFE'] ?? null);
        // @phpstan-ignore-next-line Make PHPStan happy
        $this->assertSame($backendUserAuthenticationMock, $GLOBALS['BE_USER'] ?? null);
        $this->assertCount(0, $stackPropertyReflection->getValue($stateManager));
        // restore on empty stack resets environment to empty state - expected !
        $stateManager->restore();
        $this->assertNull($GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertNull($GLOBALS['TSFE'] ?? null);
        $this->assertNull($GLOBALS['BE_USER'] ?? null);
        // assert event count
        $this->assertCount(2, $dispatchedApplyEvents);
    }

    private function createEnvironmentBuilderFactoryMock(
        EnvironmentBuilderInterface $frontendEnvironmentBuilder,
    ): MockObject&EnvironmentBuilderFactoryInterface {
        $environmentBuilderFactoryMock = $this->createMock(EnvironmentBuilderFactoryInterface::class);
        $environmentBuilderFactoryMock
            ->method('create')
            ->willReturnCallback(function (ApplicationType $applicationType) use ($frontendEnvironmentBuilder): EnvironmentBuilderInterface {
                if ($applicationType === ApplicationType::BACKEND) {
                    throw new \RuntimeException(
                        'Only frontend applicationType implemented for mocked environmentFactoryMock',
                        1762298777,
                    );
                }
                return $frontendEnvironmentBuilder;
            });

        return $environmentBuilderFactoryMock;
    }

    private function createEnvironmentBuilderMock(): MockObject&EnvironmentBuilderInterface
    {
        return $this->createMock(EnvironmentBuilderInterface::class);
    }

    private function createStateManager(
        ?EnvironmentBuilderFactoryInterface $environmentBuilderFactory = null,
    ): StateManager {
        $environmentBuilderFactory ??= $this->get(EnvironmentBuilderFactoryInterface::class);
        return new StateManager(
            environmentBuilderFactory: $environmentBuilderFactory,
        );
    }
}
