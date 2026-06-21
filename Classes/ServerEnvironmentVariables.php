<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Single source of truth for the `$_SERVER` keys the environment state manager backs up, applies and
 * resets. The list previously lived as a duplicated property in the helper trait and in both
 * version-specific frontend environment builders.
 *
 * @internal Internal implementation detail of the shipped environment builders and state managers;
 *           not part of the public API.
 */
#[Exclude]
final class ServerEnvironmentVariables
{
    /**
     * @var list<string>
     */
    public const NAMES = [
        'HTTP_HOST',
        'SERVER_NAME',
        'HTTPS',
        'SCRIPT_FILENAME',
        'SCRIPT_NAME',
        'REMOTE_ADDR',
        'REQUEST_URI',
    ];
}
