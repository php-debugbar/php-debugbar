<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../resources');

$app = new \Slim\Slim();
$app->get('/', function () use ($app) {
    $app->getLog()->info('hello world');
    render_demo_page();
});

$debugbar->addCollector(new DebugBar\Bridge\SlimCollector($app));

$app->run();
