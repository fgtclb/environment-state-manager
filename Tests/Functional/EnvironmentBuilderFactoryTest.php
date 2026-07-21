<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use FGTCLB\EnvironmentStateManager\Core13\BackendEnvironmentBuilder as Core13BackendEnvironmentBuilder;
use FGTCLB\EnvironmentStateManager\Core13\FrontendEnvironmentBuilder as Core13FrontendEnvironmentBuilder;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactory;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class EnvironmentBuilderFactoryTest extends AbstractEnvironmentStateManagerTestCase
{
    #[Test]
    public function publicServiceCanBeInstantiatedBasedOnInterface(): void
    {
        $factory = GeneralUtility::makeInstance(EnvironmentBuilderFactoryInterface::class);
        $this->assertInstanceOf(EnvironmentBuilderFactoryInterface::class, $factory);
        $this->assertInstanceOf(EnvironmentBuilderFactory::class, $factory);
    }

    #[Test]
    public function publicServiceCanBeInstantiatedBasedOnClassName(): void
    {
        $factory = GeneralUtility::makeInstance(EnvironmentBuilderFactory::class);
        $this->assertInstanceOf(EnvironmentBuilderFactoryInterface::class, $factory);
        $this->assertInstanceOf(EnvironmentBuilderFactory::class, $factory);
    }

    #[Test]
    public function createReturnsTypoV13FrontendEnvironmentBuilderInstance(): void
    {
        $stateBuildContext = new StateBuildContext(
            applicationType: ApplicationType::FRONTEND,
            pageId: null,
            languageId: null,
        );
        $builder = GeneralUtility::makeInstance(EnvironmentBuilderFactory::class)->create($stateBuildContext);
        $this->assertInstanceOf(Core13FrontendEnvironmentBuilder::class, $builder);
    }

    #[Test]
    public function createReturnsTypoV13BackendEnvironmentBuilderInstance(): void
    {
        $stateBuildContext = new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: null,
            languageId: null,
        );
        $builder = GeneralUtility::makeInstance(EnvironmentBuilderFactory::class)->create($stateBuildContext);
        $this->assertInstanceOf(Core13BackendEnvironmentBuilder::class, $builder);
    }
}
