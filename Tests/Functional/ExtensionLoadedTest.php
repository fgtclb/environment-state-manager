<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use FGTCLB\EnvironmentStateManager\Tests\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractEnvironmentStateManagerTestCase
{
    use ExtensionsLoadedTestsTrait;

    /**
     * @var array<int, string>
     */
    private static array $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/environment-state-manager',
        // extension keys
        'environment_state_manager',
    ];
}
