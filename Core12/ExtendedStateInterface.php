<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\StateInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Extended state interface for the methods specific to TYPO3 v12.
 *
 * This interface is part of the public API, but is TYPO3 v12 specific. Type-hint the version-agnostic
 * {@see StateInterface} in code that should work across core versions, and only reference this
 * interface when you explicitly need to handle TYPO3 v12 specific state.
 *
 * The TypoScriptFrontendController accessors live here, and not on the version-agnostic
 * {@see StateInterface}, because the TypoScriptFrontendController is deprecated in TYPO3 v13 and
 * removed in TYPO3 v14; a future TYPO3 v14 extended interface will simply omit them.
 */
interface ExtendedStateInterface extends StateInterface
{
    public function withTypoScriptFrontendController(?TypoScriptFrontendController $typoScriptFrontendController = null): self;
    public function typoScriptFrontendController(): ?TypoScriptFrontendController;
}
