<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

/** @noinspection PhpUnhandledExceptionInspection */
return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
        DeclareStrictTypesFixer::class,
        SingleBlankLineAtEofFixer::class,
    ])
    ->withPreparedSets(
        arrays: true,
        comments: true,
        spaces: true,
        namespaces: true,
    );
