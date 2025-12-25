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

use DebugBar\DataFormatter\DataFormatterInterface;
use DebugBar\DataFormatter\DebugBarVarDumper;
use DebugBar\DataFormatter\HasDataFormatter;
use DebugBar\DataFormatter\HasDataHasher;
use DebugBar\DataFormatter\HasXdebugLinks;
use DebugBar\DataHasher;

/**
 * Abstract class for data collectors
 */
abstract class DataCollector implements DataCollectorInterface
{
    use HasDataFormatter;
    use HasXdebugLinks;
    use HasDataHasher;

    public static ?DataFormatterInterface $defaultDataFormatter = null;
    public static ?DebugBarVarDumper $defaultVarDumper = null;
    public static ?DataHasher $dataHasher = null;

}
