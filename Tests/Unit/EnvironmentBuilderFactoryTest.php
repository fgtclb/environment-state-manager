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
        $factory = new EnvironmentBuilderFactory($frontendEnvironmentBuilder);
        $stateBuildContext = new StateBuildContext(ApplicationType::FRONTEND);

        $this->assertSame($frontendEnvironmentBuilder, $factory->create($stateBuildContext));
    }

    #[Test]
    public function createThrowsExceptionForBackendContext(): void
    {
        $factory = new EnvironmentBuilderFactory($this->createMock(EnvironmentBuilderInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1762256802);

        $factory->create(new StateBuildContext(ApplicationType::BACKEND));
    }
}
