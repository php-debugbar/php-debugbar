<?php

declare(strict_types=1);

// Adds the Content-Security-Policy to the HTTP header.
use DebugBar\DataCollector\Message\LinkMessage;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;

header("Content-Security-Policy: default-src 'self' 'nonce-demo'; img-src data:");

/** @var \DebugBar\DebugBar|array{messages: MessagesCollector,time: TimeDataCollector} $debugbar */

include 'bootstrap.php';

// PSR Interpolation
$debugbar['messages']->log('info', 'Hello {name}!', ['name' => 'World', 'location' => 'Earth']);
$debugbar['messages']->log('info', new LinkMessage('Checkout the documentation on phpdebugbar.com', 'https://phpdebugbar.com'));

$debugbar['time']->startMeasure('op1', 'sleep 500');
usleep(300);
$debugbar['time']->startMeasure('op2', 'sleep 400');
usleep(200);
$debugbar['time']->stopMeasure('op1', ['foo' => 'bar']);
usleep(200);
$debugbar['time']->stopMeasure('op2');

$debugbar['messages']->addMessage('This is a demo', 'warning');

// Object with extra context
$debugbar['messages']->addMessage(['toto' => ['titi']], 'debug', ['foo' => 'bar']);

$debugbar['messages']->addMessage('oups', 'error');
$debugbar['messages']->addMessage('welcome!', 'success');
$debugbar['messages']->addMessage('panic!', 'critical');
$debugbar["messages"]->addMessage("<!--<script>");
$debugbar["messages"]->addMessage("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur et nibh non est ultrices suscipit a non arcu. Sed sit amet est vel mi facilisis varius. Pellentesque vitae rutrum massa. Praesent magna diam, viverra eu nibh a, commodo aliquam mi. Morbi placerat tortor nec efficitur vestibulum. Nunc imperdiet feugiat massa, eu ornare dui tincidunt eget. Suspendisse accumsan hendrerit ex a iaculis. Nullam molestie sapien sed sapien feugiat tempor. Vivamus mollis vitae arcu vel commodo. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque sollicitudin purus vitae mi rutrum, sit amet molestie felis pellentesque. Vivamus in pellentesque nisl, ut lobortis metus. In hac habitasse platea dictumst.");

require __DIR__ . '/collectors/counter.php';
require __DIR__ . '/collectors/templates.php';
require __DIR__ . '/collectors/pdo.php';
require __DIR__ . '/collectors/http.php';
require __DIR__ . '/collectors/monolog.php';
require __DIR__ . '/collectors/symfony_mailer.php';

$debugbar['time']->startMeasure('render');

render_demo_page(function () {
    ?>
<h2>Index</h2>
<ul>
    <li><a href="index.php">Index page</a></li>
</ul>
<h2>AJAX</h2>
<ul>
    <li><a href="ajax.php" class="ajax">load content with fetch()</a></li>
    <li><a href="ajax.php" class="xhr">load content with an XMLHttpRequest</a></li>
    <li><a href="ajax_exception.php" class="ajax">load ajax content with exception</a></li>
    <li><a href="ajax_stack.php" class="ajax">load ajax content with stacked redirect</a></li>

</ul>
<div id="ajax-result"></div>
<h2>IFRAMES</h2>
<ul>
    <li><a href="iframes/index.php">load through iframes</a></li>
</ul>
<h2>Symfony</h2>
<ul>
    <li><a href="symfony.php">Symfony demo</a></li>
</ul>
<h2>Stack</h2>
<ul>
    <li><a href="stack.php">perform a redirect</a></li>
</ul>
<?php
});
