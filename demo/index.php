<?php

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
$debugbar['messages']->addMessage(array('toto' => array('titi', 'tata')));
$debugbar['messages']->addMessage('oups', 'error');

$classDemo = array('FirstClass', 'SecondClass', 'ThirdClass');
$debugbar->addCollector(new \DebugBar\DataCollector\ObjectCountCollector());
for ($i = 0; $i <=20; $i++) {
    $debugbar['counter']->countClass($classDemo[rand(0, 2)]);
}

$debugbar['time']->startMeasure('render');

render_demo_page(function() {
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
<h2>PDO</h2>
<ul>
    <li><a href="pdo.php">PDO demo</a></li>
</ul>
<h2>Bridges</h2>
<p>(you need to install needed dependencies first, run <code>composer.phar install</code> in each demo folders)</p>
<ul>
    <li><a href="bridge/cachecache">CacheCache</a></li>
    <li><a href="bridge/doctrine">Doctrine</a></li>
    <li><a href="bridge/monolog">Monolog</a></li>
    <li><a href="bridge/propel">Propel</a></li>
    <li><a href="bridge/slim">Slim</a></li>
    <li><a href="bridge/swiftmailer">Swift mailer</a></li>
    <li><a href="bridge/symfonymailer">Symfony mailer</a></li>
    <li><a href="bridge/twig">Twig</a></li>
</ul>
<?php
});
