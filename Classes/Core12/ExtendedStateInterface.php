<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Extended state interface for the methods specific to TYPO3 v12.
 *
 * This interface is part of the public API, but is TYPO3 v12 specific. Type-hint the version-agnostic
 * {@see StateInterface} in code that should work across core versions, and only reference this
 * interface when you explicitly need to handle TYPO3 v12 specific state.
 *
 * The `#[Exclude]` attribute is set on purpose. It keeps this interface from being compiled
 * early into the dependency injection container, which would otherwise trigger missing-class
 * and similar errors for unrelated TYPO3 versions. The TYPO3 version-aware configuration is
 * handled and re-enabled in the `EXT:environment_state_manager/Configuration/Services.php` file.
 */
#[Exclude]
interface ExtendedStateInterface extends StateInterface {}
