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
    // The HTML var dumper requires debug bar users to support the new inline assets, which not all
    // may support yet - so return false by default for now.
    protected bool $useHtmlVarDumper = false;
    protected ?DataFormatterInterface $dataFormatter = null;
    protected ?DebugBarVarDumper $varDumper = null;

    /**
     * Sets a flag indicating whether the Symfony HtmlDumper will be used to dump variables for
     * rich variable rendering.
     *
     *
     * @return $this
     */
    public function useHtmlVarDumper(bool $value = true): self
    {
        $this->useHtmlVarDumper = $value;
        return $this;
    }

    /**
     * Indicates whether the Symfony HtmlDumper will be used to dump variables for rich variable
     * rendering.
     *
     */
    public function isHtmlVarDumperUsed(): bool
    {
        return $this->useHtmlVarDumper;
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
            DataCollector::$defaultDataFormatter = new DataFormatter();
        }
        return DataCollector::$defaultDataFormatter;
    }

    /**
     * Sets the data formater instance used by this collector
     *
     * @return $this
     */
    public function setDataFormatter(DataFormatterInterface $formatter): self
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
    /**
     * Sets the default variable dumper used by all collectors subclassing this class
     *
     */
    public static function setDefaultVarDumper(DebugBarVarDumper $varDumper): void
    {
        DataCollector::$defaultVarDumper = $varDumper;
    }

    /**
     * Returns the default variable dumper
     *
     */
    public static function getDefaultVarDumper(): DebugBarVarDumper
    {
        if (DataCollector::$defaultVarDumper === null) {
            DataCollector::$defaultVarDumper = new DebugBarVarDumper();
        }
        return DataCollector::$defaultVarDumper;
    }

    /**
     * Sets the variable dumper instance used by this collector
     *
     * @return $this
     */
    public function setVarDumper(DebugBarVarDumper $varDumper): self
    {
        $this->varDumper = $varDumper;
        return $this;
    }

    /**
     * Gets the variable dumper instance used by this collector; note that collectors using this
     * instance need to be sure to return the static assets provided by the variable dumper.
     *
     */
    public function getVarDumper(): DebugBarVarDumper
    {
        if ($this->varDumper === null) {
            $this->varDumper = DataCollector::getDefaultVarDumper();
        }
        return $this->varDumper;
    }
}
