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

trait HasDataFormatter
{
    protected ?DataFormatterInterface $dataFormatter = null;

    /**
     * Indicates whether the Symfony HtmlDumper will be used to dump variables for rich variable
     * rendering.
     *
     */
    public function isHtmlVarDumperUsed(): bool
    {
        return $this->dataFormatter instanceof HtmlDataFormatter;
    }

    /**
     * Sets the default data formater instance used by all collectors subclassing this class
     *
     */
    public static function setDefaultDataFormatter(DataFormatterInterface $formater): void
    {
        DataCollector::$defaultDataFormatter = $formater;
    }

    /**
     * Returns the default data formater
     *
     */
    public static function getDefaultDataFormatter(): DataFormatterInterface
    {
        if (DataCollector::$defaultDataFormatter === null) {
            DataCollector::$defaultDataFormatter = new HtmlDataFormatter();
        }
        return DataCollector::$defaultDataFormatter;
    }

    /**
     * Sets the data formater instance used by this collector
     *
     * @return $this
     */
    public function setDataFormatter(DataFormatterInterface $formatter): static
    {
        $this->dataFormatter = $formatter;
        return $this;
    }

    public function getDataFormatter(): DataFormatterInterface
    {
        if ($this->dataFormatter === null) {
            $this->dataFormatter = DataCollector::getDefaultDataFormatter();
        }
        return $this->dataFormatter;
    }
}
