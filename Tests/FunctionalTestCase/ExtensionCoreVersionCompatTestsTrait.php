<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\FunctionalTestCase;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;

const TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION = 12;
const TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION = 13;

trait ExtensionCoreVersionCompatTestsTrait
{
    #[Test]
    public function allowedMajorTypo3Version(): void
    {
        $this->assertContains((new Typo3Version())->getMajorVersion(), $this->getAllowedMajorVersions());
    }

    #[Group('not-core-' . TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION)]
    #[Test]
    public function verifyLowestSupportedMajorVersion(): void
    {
        $this->assertSame(TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION, (new Typo3Version())->getMajorVersion());
    }

    #[Group('not-core-' . TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION)]
    #[Test]
    public function verifyHighestSupportedMajorVersion(): void
    {
        $this->assertSame(TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION, (new Typo3Version())->getMajorVersion());
    }

    /**
     * @return array<int, int>
     */
    private function getAllowedMajorVersions(): array
    {
        return [
            $this->getLowestSupportedTYPO3MajorVersion(),
            $this->getHighestSupportedTYPO3MajorVersion(),
        ];
    }

    private function getLowestSupportedTYPO3MajorVersion(): int
    {
        return TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION;
    }

    private function getHighestSupportedTYPO3MajorVersion(): int
    {
        return TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION;
    }
}
