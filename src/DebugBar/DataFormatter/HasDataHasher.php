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

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataHasher;
use DebugBar\DebugBarException;

trait HasDataHasher
{
    public static function setDataHasher(DataHasher $dataHasher): void
    {
        DataCollector::$dataHasher = $dataHasher;
    }

    public static function hasDataHasher(): bool
    {
        return DataCollector::$dataHasher !== null;
    }

    public static function getDataHasher(): DataHasher
    {
        if (DataCollector::$dataHasher === null) {
            throw new DebugBarException("Not DataHasher is set in DebugBar");
        }

        return DataCollector::$dataHasher;
    }

}
