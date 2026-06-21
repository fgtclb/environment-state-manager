<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\StateInterface;

/**
 * Extended state interface for the methods specific to TYPO3 v12.
 *
 * This interface is part of the public API, but is TYPO3 v12 specific. Type-hint the version-agnostic
 * {@see StateInterface} in code that should work across core versions, and only reference this
 * interface when you explicitly need to handle TYPO3 v12 specific state.
 */
interface ExtendedStateInterface extends StateInterface {}
