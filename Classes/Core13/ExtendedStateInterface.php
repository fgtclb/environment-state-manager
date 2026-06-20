<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core13;

use FGTCLB\EnvironmentStateManager\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Extended state interface for the methods specific to TYPO3 v13.
 *
 * The `#[Exclude]` attribute is set on purpose. It keeps this interface from being compiled
 * early into the dependency injection container, which would otherwise trigger missing-class
 * and similar errors for unrelated TYPO3 versions. The TYPO3 version-aware configuration is
 * handled and re-enabled in the `EXT:environment_state_manager/Configuration/Services.php` file.
 *
 * @internal only for use within `EXT:environment_state_manager` and dependent extensions; not part of the public API.
 */
#[Exclude]
interface ExtendedStateInterface extends StateInterface {}
