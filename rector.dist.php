<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

$rectorConfig = RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withSkip([
        SafeDeclareStrictTypesRector::class,
    ])
    ->withPhpSets();

$overrideFiles = [
    __DIR__ . '/rector.custom.php',
    __DIR__ . '/rector.local.php',
];

foreach ($overrideFiles as $overrideFile) {
    if (! file_exists($overrideFile)) {
        continue;
    }

    $overrideCallback = include $overrideFile;

    assert(
        is_callable($overrideCallback),
        'The rector.custom.php file must return a callable.'
    );

    $overrideCallback($rectorConfig);
}

return $rectorConfig;
