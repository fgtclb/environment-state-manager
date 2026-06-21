<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Information\Typo3Version;

return static function (
    ContainerConfigurator $configurator,
    ContainerBuilder $builder,
): void {
    $majorVersion = (new Typo3Version())->getMajorVersion();

    $services = $configurator->services();

    // Default configuration: autowire and autoconfigure, keep services private.
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    // Version-agnostic sources of the extension.
    $services->load(
        'FGTCLB\\EnvironmentStateManager\\',
        __DIR__ . '/../Classes/*',
    );

    // TYPO3 core-version specific sources: only the folder matching the running
    // TYPO3 major version is loaded. The concrete services are published and
    // wired through Symfony dependency injection attributes on the classes
    // themselves (#[AsAlias], #[Autoconfigure], #[Autowire]).
    $services->load(
        sprintf('FGTCLB\\EnvironmentStateManager\\Core%d\\', $majorVersion),
        sprintf(__DIR__ . '/../Core%d/*', $majorVersion),
    );
};
