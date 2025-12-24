<?php

declare(strict_types=1);

/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects info about PHP
 */
class PhpInfoCollector extends DataCollector implements Renderable
{
    public function getName(): string
    {
        return 'php';
    }

    public function collect(): array
    {
        return [
            'version' => implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]),
            'interface' => PHP_SAPI,
        ];
    }

    public function getWidgets(): array
    {
        return [
            "php_version" => [
                "icon" => "code",
                "tooltip" => "PHP Version",
                "map" => "php.version",
                "default" => "",
            ],
        ];
    }
}
