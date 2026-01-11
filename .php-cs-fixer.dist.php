<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(true)
    ->setRules([
        '@auto' => true,
        '@PER-CS' => true,
        '@PHP8x2Migration' => true,
        '@PHPUnit10x0Migration:risky' => true,
        //Modernize code
        'array_push'              => true,
        'modernize_strpos'        => true,
        'modernize_types_casting' => true,
        //Align arrays
        'trim_array_spaces'      => true,
        //Casting
        'no_short_bool_cast' => true,
        'cast_spaces'        => true,

        // Class names
        'no_leading_namespace_whitespace' => true,
        'no_unused_imports'               => true,
        'single_space_around_construct'   => true,

        //Remove unneeded code
        'no_unneeded_braces'          => true,
        'no_useless_else'             => true,
        'no_useless_return'           => true,
        'no_extra_blank_lines'        => true,

        //PHPdocs
        'no_superfluous_phpdoc_tags'    => true,
        'no_empty_phpdoc'               => true,
        'phpdoc_align'                  => true,
        'phpdoc_separation'             => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_return_type' => true,

        // Strict
        'declare_strict_types'        => true,
        'return_type_declaration'     => true,
        'nullable_type_declaration_for_default_null_value' => true,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // 💡 root folder to check
            ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/demo'])
            // 💡 additional files, eg bin entry file
            // ->append([__DIR__.'/bin-entry-file'])
            // 💡 folders to exclude, if any
            // ->exclude([/* ... */])
            // 💡 path patterns to exclude, if any
            // ->notPath([/* ... */])
            // 💡 extra configs
            // ->ignoreDotFiles(false) // true by default in v3, false in v4 or future mode
            // ->ignoreVCS(true) // true by default
    )
;
