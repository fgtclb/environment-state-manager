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

        $this->assertSame(ApplicationType::FRONTEND, $context->applicationType);
        $this->assertNull($context->pageId);
        $this->assertNull($context->languageId);
        $this->assertNull($context->backendUserId);
        $this->assertNull($context->workspaceId);
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

        $this->assertSame(ApplicationType::BACKEND, $context->applicationType);
        $this->assertSame(42, $context->pageId);
        $this->assertSame(1, $context->languageId);
        $this->assertSame(3, $context->backendUserId);
        $this->assertSame(2, $context->workspaceId);
    }
}
