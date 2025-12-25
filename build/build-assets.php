<?php

include __DIR__ . '/../vendor/autoload.php';

// for stack data
session_start();

use DebugBar\StandardDebugBar;

$debugbar = new StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer();

$assets = $debugbarRenderer->getDistIncludedAssets();
$debugbarRenderer->dumpAssets(files: $assets['css'], targetFilename: __DIR__ . '/../dist/debugbar.css');
$debugbarRenderer->dumpAssets(files: $assets['js'], targetFilename: __DIR__ . '/../dist/debugbar.js');