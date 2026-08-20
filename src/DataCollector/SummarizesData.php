<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

/**
 * Helpers for collectors building the `summary` part of their collected data.
 */
trait SummarizesData
{
    /**
     * Flattens and truncates a value so it fits on a single summary line.
     *
     * Summaries are read as a whole, so truncation is marked explicitly rather than
     * silently dropping the tail.
     */
    protected function summarizeText(string $text, int $maxLength = 200): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength - 1) . '…' : $text;
    }
}
