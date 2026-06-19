<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Exception;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;

/**
 * This exception indicates that no suiting SiteConfiguration could be automatically determined.
 *
 * Thrown in {@see EnvironmentBuilderInterface::build()} implementations.
 */
final class SiteConfigCouldNotBeDetermined extends \RuntimeException {}
