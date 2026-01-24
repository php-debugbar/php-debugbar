<?php

declare(strict_types=1);

namespace DebugBar\DataCollector\Message;

readonly class LinkMessage implements MessageInterface
{
    public function __construct(private readonly string $text, private readonly string $url) {}

    public function getText(): string
    {
        return $this->text . ': ' . $this->url;
    }

    public function getHtml(): ?string
    {
        return '<a href="' . htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="phpdebugbar-widgets-external-link">' . htmlspecialchars($this->text, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
