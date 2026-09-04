<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

$rectorConfig = RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets();

$overrideFiles = [
    __DIR__ . '/rector.custom.php',
    __DIR__ . '/rector.local.php',
];

foreach ($overrideFiles as $overrideFile) {
    if (! file_exists($overrideFile)) {
        continue;
    }

    $overrideCallback = include __DIR__ . '/rector.custom.php';

    assert(
        is_callable($overrideCallback),
        'The rector.custom.php file must return a callable.'
    );

    $overrideCallback($rectorConfig);
}

return $rectorConfig;
