<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Unit;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactory;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EnvironmentBuilderFactoryTest extends UnitTestCase
{
    #[Test]
    public function createReturnsConfiguredFrontendEnvironmentBuilderForFrontendContext(): void
    {
        $frontendEnvironmentBuilder = $this->createMock(EnvironmentBuilderInterface::class);
        $backendEnvironmentBuilder = $this->createMock(EnvironmentBuilderInterface::class);
        $factory = new EnvironmentBuilderFactory($frontendEnvironmentBuilder, $backendEnvironmentBuilder);
        $stateBuildContext = new StateBuildContext(ApplicationType::FRONTEND);

        $this->assertSame($frontendEnvironmentBuilder, $factory->create($stateBuildContext));
    }

    #[Test]
    public function createReturnsConfiguredBackendEnvironmentBuilderForBackendContext(): void
    {
        $frontendEnvironmentBuilder = $this->createMock(EnvironmentBuilderInterface::class);
        $backendEnvironmentBuilder = $this->createMock(EnvironmentBuilderInterface::class);
        $factory = new EnvironmentBuilderFactory($frontendEnvironmentBuilder, $backendEnvironmentBuilder);
        $stateBuildContext = new StateBuildContext(ApplicationType::BACKEND);

        $this->assertSame($backendEnvironmentBuilder, $factory->create($stateBuildContext));
    }
}
