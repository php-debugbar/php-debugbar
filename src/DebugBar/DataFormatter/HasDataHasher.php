<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataHasher;
use DebugBar\DebugBarException;

trait HasDataHasher
{
    protected ?DataHasher $dataHasher = null;

    public static function setDefaultDataHasher(DataHasher $dataHasher): void
    {
        DataCollector::$defaultDataHasher = $dataHasher;
    }

    public static function getDefaultDataHasher(): DataHasher
    {
        if (DataCollector::$defaultDataHasher === null) {
            throw new DebugBarException("Not DataHasher is set in DebugBar");
        }

        return DataCollector::$defaultDataHasher;
    }

    public function setDataHasher(DataHasher $dataHasher): void
    {
        $this->dataHasher = $dataHasher;
    }

    public function hasDataHasher(): bool
    {
        return $this->dataHasher !== null || static::$defaultDataHasher !== null;
    }

    /**
     * @throws DebugBarException
     */
    public function getDataHasher(): DataHasher
    {
        if ($this->dataHasher === null) {
            $this->dataHasher = DataCollector::getDefaultDataHasher();
        }

        return $this->dataHasher;
    }

    public function getSignature(string $action, mixed $payload = null): string
    {
        return $this->getDataHasher()->sign([
            'collector' => $this->getName(),
            'action' => $action,
            'payload' => $payload,
        ]);
    }
}
