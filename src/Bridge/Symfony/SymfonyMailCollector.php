<?php

declare(strict_types=1);

namespace DebugBar\Bridge\Symfony;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\Mime\Part\AbstractPart;

/**
 * Collects data about sent mail events
 *
 * https://github.com/symfony/mailer
 */
class SymfonyMailCollector extends DataCollector implements Renderable, AssetProvider
{
    private array $messages = [];

    private bool $showBody = false;

    public function addSymfonyMessage(\Symfony\Component\Mailer\SentMessage $message): void
    {
        $this->messages[] = $message->getOriginalMessage();
    }

    public function showMessageBody(bool $show = true): static
    {
        $this->showBody = $show;

        return $this;
    }

    public function collect(): array
    {
        $mails = [];

        foreach ($this->messages as $message) {
            /* @var \Symfony\Component\Mime\Message $message */
            $mail = [
                'to' => array_map(function ($address) {
                    /* @var \Symfony\Component\Mime\Address $address */
                    return $address->toString();
                }, $message->getTo()),
                'subject' => $message->getSubject(),
                'headers' => $message->getHeaders()->toString(),
                'body' => null,
                'html' => null,
            ];

            if ($this->showBody) {
                $body = $message->getBody();
                if ($body instanceof AbstractPart) {
                    $mail['html'] = $message->getHtmlBody();
                    $mail['body'] = $message->getTextBody();
                } else {
                    $mail['body'] = $body->bodyToString();
                }
            }

            $mails[] = $mail;
        }

        return [
            'count' => count($mails),
            'mails' => $mails,
        ];
    }

    public function getName(): string
    {
        return 'symfonymailer_mails';
    }

    public function getWidgets(): array
    {
        return [
            'emails' => [
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.MailsWidget',
                'map' => 'symfonymailer_mails.mails',
                'default' => '[]',
                'title' => 'Mails',
            ],
            'emails:badge' => [
                'map' => 'symfonymailer_mails.count',
                'default' => 'null',
            ],
        ];
    }

    public function getAssets(): array
    {
        return [
            'css' => 'widgets/mails/widget.css',
            'js' => 'widgets/mails/widget.js',
        ];
    }
}
