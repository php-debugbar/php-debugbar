<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

use DebugBar\StandardDebugBar;

$debugbar = new \DebugBar\DebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer();

$assets = $debugbarRenderer->getDistIncludedAssets();
$debugbarRenderer->dumpAssets(files: $assets['css'], targetFilename: __DIR__ . '/../dist/debugbar.css');
$debugbarRenderer->dumpAssets(files: $assets['js'], targetFilename: __DIR__ . '/../dist/debugbar.js');