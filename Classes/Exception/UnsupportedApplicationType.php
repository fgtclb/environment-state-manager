<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Exception;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;

/**
 * This exception indicates that no environment builder exists for the requested application type.
 *
 * The extension builds frontend and backend environments. TYPO3 v14 added
 * `ApplicationType::INSTALL`, for which no environment can be built.
 *
 * Thrown in {@see EnvironmentBuilderFactoryInterface::create()} implementations.
 */
final class UnsupportedApplicationType extends \RuntimeException {}
