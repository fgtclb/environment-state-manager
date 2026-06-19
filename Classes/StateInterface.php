<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Interface defining the shared methods across supported TYPO3 version,
 * used to implement version specific implementations.
 *
 * @internal only to be used within `EXT:environment_state_manager` and depending extensions and not part of public API.
 */
interface StateInterface
{
    public function withRequest(?ServerRequestInterface $request = null): self;
    public function request(): ?ServerRequestInterface;
    public function withTypoScriptFrontendController(?TypoScriptFrontendController $typoScriptFrontendController = null): self;
    public function typoScriptFrontendController(): ?TypoScriptFrontendController;
    public function withPageRenderer(?PageRenderer $pageRenderer = null): self;
    public function pageRenderer(): ?PageRenderer;
    public function withBackendUserAuthentication(?BackendUserAuthentication $backendUserAuthentication = null): self;
    public function backendUserAuthentication(): ?BackendUserAuthentication;
    public function withContext(?Context $context): self;
    public function context(): ?Context;

    /**
     * @param array<int|string, mixed> $data
     */
    public function withAdditionalData(string $key, array $data): self;

    /**
     * @return array<int|string, mixed>|null Returns null in case $key does not exist, otherwise the data array.
     */
    public function additionalData(string $key): ?array;

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function completeAdditionalData(): array;
}
