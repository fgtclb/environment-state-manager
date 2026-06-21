<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Core12;

use FGTCLB\EnvironmentStateManager\StateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Holds a single environment snapshot, either populated from the current environment or used to
 * create a new one. TYPO3 v12 only.
 *
 * The `#[Exclude]` attribute keeps this class out of the dependency injection container: it is a
 * data transfer object created explicitly through the environment builder and state manager, never
 * resolved as a service.
 *
 * @internal Concrete, TYPO3 v12 specific implementation of {@see StateInterface} (and
 *           {@see ExtendedStateInterface}). Created through the environment builder and the state
 *           manager — type-hint the interface, not this class. Not covered by the extension's
 *           public-API backward-compatibility promise.
 */
#[Exclude]
final class State implements StateInterface, ExtendedStateInterface
{
    /**
     * @param array<string, array<int|string, mixed>> $additionalData
     */
    public function __construct(
        private readonly ?ServerRequestInterface $request = null,
        private readonly ?TypoScriptFrontendController $typoScriptFrontendController = null,
        private readonly ?PageRenderer $pageRenderer = null,
        private readonly ?Context $context = null,
        private readonly ?BackendUserAuthentication $backendUserAuthentication = null,
        private readonly ?LanguageService $languageService = null,
        private readonly array $additionalData = [],
    ) {}

    public function context(): ?Context
    {
        return $this->context;
    }

    public function request(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function typoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $this->typoScriptFrontendController;
    }

    public function pageRenderer(): ?PageRenderer
    {
        return $this->pageRenderer;
    }

    public function backendUserAuthentication(): ?BackendUserAuthentication
    {
        return $this->backendUserAuthentication;
    }

    public function languageService(): ?LanguageService
    {
        return $this->languageService;
    }

    /**
     * @return array<int|string, mixed>|null Returns null in case $key does not exist, otherwise the data array.
     */
    public function additionalData(string $key): ?array
    {
        return array_key_exists($key, $this->additionalData)
            ? $this->additionalData[$key]
            : null;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function completeAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function withContext(?Context $context): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $this->additionalData,
        );
    }

    public function withRequest(?ServerRequestInterface $request = null): self
    {
        return new self(
            request: $request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $this->context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $this->additionalData,
        );
    }

    public function withTypoScriptFrontendController(?TypoScriptFrontendController $typoScriptFrontendController = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $this->context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $this->additionalData,
        );
    }

    public function withPageRenderer(?PageRenderer $pageRenderer = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $pageRenderer,
            context: $this->context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $this->additionalData,
        );
    }

    public function withBackendUserAuthentication(?BackendUserAuthentication $backendUserAuthentication = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $this->context,
            backendUserAuthentication: $backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $this->additionalData,
        );
    }

    public function withLanguageService(?LanguageService $languageService = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $this->context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $languageService,
            additionalData: $this->additionalData,
        );
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function withAdditionalData(string $key, array $data): self
    {
        $additionalData = $this->additionalData;
        $additionalData[$key] = $data;
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            pageRenderer: $this->pageRenderer,
            context: $this->context,
            backendUserAuthentication: $this->backendUserAuthentication,
            languageService: $this->languageService,
            additionalData: $additionalData,
        );
    }
}
