<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

use DebugBar\StandardDebugBar;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataFormatter\HtmlDataFormatter;
use DebugBar\DataFormatter\JsonDataFormatter;
use DebugBar\DataFormatter\VarDumper\DebugBarHtmlDumper;

DataCollector::setDefaultDataFormatter(new JsonDataFormatter());

// Generate dist bundles
$debugbar = new \DebugBar\DebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer();

$assets = $debugbarRenderer->getDistIncludedAssets();
$debugbarRenderer->dumpAssets(files: $assets['css'], targetFilename: __DIR__ . '/../resources/dist/debugbar.css');
$debugbarRenderer->dumpAssets(files: $assets['js'], targetFilename: __DIR__ . '/../resources/dist/debugbar.js');
