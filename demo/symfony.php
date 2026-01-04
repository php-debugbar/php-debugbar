<?php

declare(strict_types=1);

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\SymfonyHttpDriver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/** @var \DebugBar\DebugBar|array{messages: MessagesCollector,time: TimeDataCollector} $debugbar */

include 'bootstrap.php';
if ($debugbar->hasCollector('request')) {
    $debugbar->removeCollector('request');
}

$session = new Session(new PhpBridgeSessionStorage());

// Create from globals
$request = Request::createFromGlobals();
$httpDriver = new SymfonyHttpDriver($session);

$debugbar->setHttpDriver($httpDriver);

$ajax = $request->query->get('ajax');
if ($ajax) {
    $debugbar['messages']->addMessage('hello from ajax');

    $response = new \Symfony\Component\HttpFoundation\JsonResponse('Hello from Symfony Ajax');
} else {
    $response = new \Symfony\Component\HttpFoundation\Response(<<<HTML
        <html>
            <head>
                <script type="text/javascript" nonce="demo">
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('.ajax').forEach(function(el) {
                            el.addEventListener('click', function(event) {
                                event.preventDefault();
                                fetch(this.href)
                                    .then(response => response.text())
                                    .then(data => {
                                        document.getElementById('ajax-result').innerHTML = data;
                                    });
                            });
                        });
                    });
                </script>
            </head>
            <body>
            <h1>DebugBar Symfony Integration</h1>
            
            <h2>Index</h2>
            <ul>
                <li><a href="index.php">Index page</a></li>
            </ul>
            
            <h2>AJAX</h2>
            <ul>
                <li><a href="symfony.php?ajax=1" class="ajax">load ajax content</a></li>
            </ul>
            </body>
        </html>
        HTML);
}

$httpDriver->setResponse($response);
$debugbar->addCollector(new DebugBar\Bridge\Symfony\SymfonyRequestCollector($request, $response));

require __DIR__ . '/collectors/symfony_mailer.php';

// Inject Debugbar
if ($ajax) {
    $debugbar->sendDataInHeaders();
} else {
    $debugbar->getJavascriptRenderer()->injectInSymfonyResponse($response);
}

// Adds the Content-Security-Policy to the HTTP header.
$response->headers->add(["Content-Security-Policy" => "default-src 'self' 'nonce-demo'; img-src data:"]);

// Send the Response
$response->send();
