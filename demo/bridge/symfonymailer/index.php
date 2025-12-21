<?php

use DebugBar\Bridge\Symfony\SymfonyMailCollector;
use DebugBar\DataCollector\MessagesCollector;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../resources');

$mailCollector = new SymfonyMailCollector();
$mailCollector->showMessageDetail();
$mailCollector->showMessageBody();
$debugbar->addCollector($mailCollector);
$logger = new MessagesCollector('mails');
$debugbar['messages']->aggregate($logger);

// Add even listener for SentMessageEvent
$dispatcher = new EventDispatcher();
$dispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) use ($mailCollector): void {
    $mailCollector->addSymfonyMessage($event->getMessage());
});

// Creates NullTransport Mailer for testing
$mailer = new Mailer(new class ($dispatcher, $logger) extends AbstractTransport {
    protected function doSend(\Symfony\Component\Mailer\SentMessage $message): void
    {
        $this->getLogger()->debug('Sending message "'.$message->getOriginalMessage()->getSubject().'"');
    }
    public function __toString(): string{ return 'null://'; }
});

$email = (new Email())
    ->from('john@doe.com')
    ->to('you@example.com')
    //->cc('cc@example.com')
    //->bcc('bcc@example.com')
    //->replyTo('fabien@example.com')
    //->priority(Email::PRIORITY_HIGH)
    ->subject('Wonderful Subject')
    ->html('<div>Here is the message itself</div>');

$mailer->send($email);

render_demo_page();
