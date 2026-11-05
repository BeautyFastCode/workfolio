<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = new Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('config')
    ->notPath([
        'public/index.php',
        'importmap.php',
        'tests/bootstrap.php',
    ])
;

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        // Base rules
        '@PhpCsFixer' => true,
        '@PHP82Migration' => true,
        '@PHP83Migration' => true,
        '@PHP84Migration' => true,
        '@PHP85Migration' => true,

        // STRICT PHP
        'declare_strict_types' => true,

        // Code quality opinions
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'array_syntax' => ['syntax' => 'short'],

        // Code style
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'phpdoc_summary' => false,

        // PHPUnit consistency
        'php_unit_method_casing' => ['case' => 'snake_case'],
    ])
    ->setFinder($finder)
;
