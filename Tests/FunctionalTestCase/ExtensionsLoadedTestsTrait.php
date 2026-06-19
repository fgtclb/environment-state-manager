<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\FunctionalTestCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

trait ExtensionsLoadedTestsTrait
{
    public static function expectedLoadedExtensionIdentifiers(): \Generator
    {
        foreach (self::$expectedLoadedExtensions as $identifier) {
            yield sprintf('%s: %s', (str_contains($identifier, '/') ? 'composer package name' : 'extension key'), $identifier) => [
                'identifier' => $identifier,
            ];
        }
    }

    #[DataProvider('expectedLoadedExtensionIdentifiers')]
    #[Test]
    public function verifyLoadedExtensionByIdentifier(string $identifier): void
    {
        $this->assertTrue(ExtensionManagementUtility::isLoaded($identifier), sprintf(
            '"%s" returns true using identifier "%s" (%s)',
            sprintf('%s::%s()', ExtensionManagementUtility::class, 'isLoaded'),
            $identifier,
            (str_contains($identifier, '/') ? 'composer package name' : 'extension key'),
        ));
    }
}
