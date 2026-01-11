<?php

declare(strict_types=1);

namespace DebugBar\Storage;

abstract class AbstractStorage implements StorageInterface
{
    protected int|false $autoPrune = 24;
    protected int $autoPruneProbability = 5;

    public function setAutoPrune(int|false $autoPrune, ?int $autoPruneProbability = null): void
    {
        $this->autoPrune = $autoPrune;
        if ($autoPruneProbability !== null) {
            $this->autoPruneProbability = min(100, max(1, $autoPruneProbability));
        }
    }

    public function getAutoPrune(): ?int
    {
        return $this->autoPrune;
    }

    public function autoPrune(): void
    {
        if ($this->autoPrune === false || $this->autoPruneProbability === 0) {
            return;
        }

        if (rand(1, 100) <= $this->autoPruneProbability) {
            $this->prune($this->autoPrune);
        }
    }
}
