<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Unit\Exception;

use FGTCLB\EnvironmentStateManager\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\EnvironmentStateManager\Exception\SiteConfigCouldNotBeDetermined;
use FGTCLB\EnvironmentStateManager\Exception\UnsupportedApplicationType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards the public-API exception contract: both exceptions are RuntimeExceptions and carry the
 * message and code they are thrown with.
 */
final class PublicApiExceptionTest extends TestCase
{
    #[Test]
    public function noTypo3VersionCompatibleEnvironmentBuilderFoundIsAThrowableRuntimeException(): void
    {
        $exception = new NoTypo3VersionCompatibleEnvironmentBuilderFound('no builder', 1762800000);

        // Statically certain, but intentionally asserted: being a \RuntimeException is a public
        // API promise consumers catch on, and must not silently change.
        // @phpstan-ignore-next-line
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('no builder', $exception->getMessage());
        $this->assertSame(1762800000, $exception->getCode());
    }

    #[Test]
    public function siteConfigCouldNotBeDeterminedIsAThrowableRuntimeException(): void
    {
        $exception = new SiteConfigCouldNotBeDetermined('no site', 1762800001);

        // Statically certain, but intentionally asserted: being a \RuntimeException is a public
        // API promise consumers catch on, and must not silently change.
        // @phpstan-ignore-next-line
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('no site', $exception->getMessage());
        $this->assertSame(1762800001, $exception->getCode());
    }

    #[Test]
    public function unsupportedApplicationTypeIsAThrowableRuntimeException(): void
    {
        $exception = new UnsupportedApplicationType('unsupported', 1784672688);

        // Statically certain, but intentionally asserted: being a \RuntimeException is a public
        // API promise consumers catch on, and must not silently change.
        // @phpstan-ignore-next-line
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('unsupported', $exception->getMessage());
        $this->assertSame(1784672688, $exception->getCode());
    }
}
