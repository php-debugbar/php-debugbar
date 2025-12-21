<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../resources');

use DebugBar\Bridge\SwiftMailer\SwiftLogCollector;
use DebugBar\Bridge\SwiftMailer\SwiftMailCollector;

$mailer = new Swift_Mailer(new Swift_NullTransport());

$debugbar['messages']->aggregate(new SwiftLogCollector($mailer));
$mailCollector = new SwiftMailCollector($mailer);
$mailCollector->showMessageBody();
$debugbar->addCollector($mailCollector);

$message = (new Swift_Message('Wonderful Subject'))
  ->setFrom(array('john@doe.com' => 'John Doe'))
  ->setTo(array('receiver@domain.org', 'other@domain.org' => 'A name'))
  ->setBody('<div>Here is the message itself</div>');

$mailer->send($message);


render_demo_page();
