<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

$logger = new Monolog\Logger('demo');

$debugbar->addCollector(new DebugBar\Bridge\MonologCollector($logger));

$logger->info('hello world');

render_demo_page();
