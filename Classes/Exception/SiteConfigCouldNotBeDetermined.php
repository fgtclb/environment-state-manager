<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Exception;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderInterface;

/**
 * This exception indicates that no suitable SiteConfiguration could be determined automatically.
 *
 * Thrown in {@see EnvironmentBuilderInterface::build()} implementations.
 */
final class SiteConfigCouldNotBeDetermined extends \RuntimeException {}
