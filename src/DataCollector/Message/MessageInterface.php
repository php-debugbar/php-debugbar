<?php

declare(strict_types=1);

namespace DebugBar\DataCollector\Message;

interface MessageInterface
{
    public function getText(): string;

    public function getHtml(): ?string;
}
