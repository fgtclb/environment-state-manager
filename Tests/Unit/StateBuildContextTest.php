<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Unit;

use FGTCLB\EnvironmentStateManager\StateBuildContext;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StateBuildContextTest extends UnitTestCase
{
    #[Test]
    public function defaultsAreNullExceptApplicationType(): void
    {
        $context = new StateBuildContext(ApplicationType::FRONTEND);

        self::assertSame(ApplicationType::FRONTEND, $context->applicationType);
        self::assertNull($context->pageId);
        self::assertNull($context->languageId);
        self::assertNull($context->backendUserId);
        self::assertNull($context->workspaceId);
    }

    #[Test]
    public function allValuesAreExposedAsGiven(): void
    {
        $context = new StateBuildContext(
            applicationType: ApplicationType::BACKEND,
            pageId: 42,
            languageId: 1,
            backendUserId: 3,
            workspaceId: 2,
        );

        self::assertSame(ApplicationType::BACKEND, $context->applicationType);
        self::assertSame(42, $context->pageId);
        self::assertSame(1, $context->languageId);
        self::assertSame(3, $context->backendUserId);
        self::assertSame(2, $context->workspaceId);
    }
}
