<?php

declare(strict_types=1);

// Adds the Content-Security-Policy to the HTTP header.
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;

header("Content-Security-Policy: default-src 'self' 'nonce-demo'; img-src data:");

/** @var \DebugBar\DebugBar|array{messages: MessagesCollector,time: TimeDataCollector} $debugbar */

include 'bootstrap.php';
if ($debugbar->hasCollector('request')) {
    $debugbar->removeCollector('request');
}

// Create from globals
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$response = new \Symfony\Component\HttpFoundation\Response(<<<HTML
    <html>
        <body>
        <h1>DebugBar Symfony Integration</h1>
        
        <h2>Index</h2>
        <ul>
            <li><a href="index.php">Index page</a></li>
        </ul>
        </body>
    </html>
    HTML);

$debugbar->addCollector(new DebugBar\Bridge\Symfony\SymfonyRequestCollector($request, $response));

require __DIR__ . '/collectors/symfony_mailer.php';

// Inject Debugbar
$debugbar->getJavascriptRenderer()->injectInSymfonyResponse($response);

// Send the Response
$response->send();
