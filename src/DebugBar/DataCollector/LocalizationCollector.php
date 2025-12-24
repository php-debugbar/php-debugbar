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
 * Collects info about the current localization state
 */
class LocalizationCollector extends DataCollector implements Renderable
{
    /**
     * Get the current locale
     */
    public function getLocale(): string|false
    {
        /** @phpstan-ignore argument.type */
        return setlocale(LC_ALL, 0);
    }

    /**
     * Get the current translations domain
     */
    public function getDomain(): string|false
    {
        return textdomain(null);
    }

    public function collect(): array
    {
        return [
            'locale' => $this->getLocale(),
            'domain' => $this->getDomain(),
        ];
    }

    public function getName(): string
    {
        return 'localization';
    }

    public function getWidgets(): array
    {
        return [
            'domain' => [
                'icon' => 'bookmark',
                'map'  => 'localization.domain',
            ],
            'locale' => [
                'icon' => 'flag',
                'map'  => 'localization.locale',
            ],
        ];
    }
}
