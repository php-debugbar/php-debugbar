<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../resources');

$cache = new CacheCache\Cache(new CacheCache\Backends\Memory());

$debugbar->addCollector(new DebugBar\Bridge\CacheCacheCollector($cache));

$cache->set('foo', 'bar');
$cache->get('foo');
$cache->get('bar');

render_demo_page();
