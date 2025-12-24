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
        'single_space_after_construct'    => true,

        //Remove unneeded code
        'no_unneeded_curly_braces'    => true,
        'no_useless_else'             => true,
        'no_useless_return'           => true,
        'no_extra_blank_lines'        => true,
        'declare_strict_types'        => true,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // 💡 root folder to check
            ->in(__DIR__)
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
