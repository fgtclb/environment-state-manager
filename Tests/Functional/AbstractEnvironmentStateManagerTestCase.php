<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractEnvironmentStateManagerTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
    ];
}
