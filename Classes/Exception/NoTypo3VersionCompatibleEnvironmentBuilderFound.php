<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Exception;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactory;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;

/**
 * Indicates that no suitable {@see EnvironmentBuilderInterface} implementation can be found
 * in {@see EnvironmentBuilderFactoryInterface::create()} ({@see EnvironmentBuilderFactory::create()}).
 */
final class NoTypo3VersionCompatibleEnvironmentBuilderFound extends \RuntimeException {}
