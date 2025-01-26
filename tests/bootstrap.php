<?php

error_reporting(E_ALL);
date_default_timezone_set('Europe/London');

$loader = require(dirname(__DIR__) . '/vendor/autoload.php');
$loader->add('DebugBar\Tests', __DIR__);
