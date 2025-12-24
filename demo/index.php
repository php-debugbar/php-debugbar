<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

include 'bootstrap.php';

$debugbar['messages']->addMessage('hello');

$debugbar['time']->startMeasure('op1', 'sleep 500');
usleep(300);
$debugbar['time']->startMeasure('op2', 'sleep 400');
usleep(200);
$debugbar['time']->stopMeasure('op1');
usleep(200);
$debugbar['time']->stopMeasure('op2');

$debugbar['messages']->addMessage('world', 'warning');
$debugbar['messages']->addMessage(['toto' => ['titi', 'tata']]);
$debugbar['messages']->addMessage('oups', 'error');
$debugbar['messages']->addMessage('welcome!', 'success');
$debugbar['messages']->addMessage('panic!', 'critical');


require __DIR__ . '/collectors/counter.php';
require __DIR__ . '/collectors/templates.php';
require __DIR__ . '/collectors/pdo.php';
require __DIR__ . '/collectors/monolog.php';
require __DIR__ . '/collectors/symfony_mailer.php';


$debugbar['time']->startMeasure('render');

render_demo_page(function () {
    ?>
<h2>AJAX</h2>
<ul>
    <li><a href="ajax.php" class="ajax">load ajax content</a></li>
    <li><a href="ajax_exception.php" class="ajax">load ajax content with exception</a></li>
</ul>
<div id="ajax-result"></div>
<h2>IFRAMES</h2>
<ul>
    <li><a href="iframes/index.php">load through iframes</a></li>
</ul>
<h2>Stack</h2>
<ul>
    <li><a href="stack.php">perform a redirect</a></li>
</ul>
<?php
});
