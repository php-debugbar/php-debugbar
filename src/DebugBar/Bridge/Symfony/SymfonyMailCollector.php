<?php

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
    /** @var array */
    private $messages = array();

    /** @var bool */
    private $showDetailed = false;

    /** @var bool */
    private $showBody = false;

    /** @param \Symfony\Component\Mailer\SentMessage $message */
    public function addSymfonyMessage($message)
    {
        $this->messages[] = $message->getOriginalMessage();
    }

    public function showMessageBody($show = true)
    {
        $this->showBody = $show;
    }

    public function collect()
    {
        $mails = array();

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

        return array(
            'count' => count($mails),
            'mails' => $mails,
        );
    }

    public function getName()
    {
        return 'symfonymailer_mails';
    }

    public function getWidgets()
    {
        return array(
            'emails' => array(
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.MailsWidget',
                'map' => 'symfonymailer_mails.mails',
                'default' => '[]',
                'title' => 'Mails'
            ),
            'emails:badge' => array(
                'map' => 'symfonymailer_mails.count',
                'default' => 'null'
            )
        );
    }

    public function getAssets()
    {
        return array(
            'css' => 'widgets/mails/widget.css',
            'js' => 'widgets/mails/widget.js'
        );
    }
}
