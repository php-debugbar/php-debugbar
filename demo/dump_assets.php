<?php

include 'bootstrap.php';

if (!isset($_GET['type'])) {
    $_GET['type'] = 'js';
}

if ($_GET['type'] == 'css') {
    header('content-type', 'text/css');
    $debugbarRenderer->dumpCssAssets();
} elseif ($_GET['type'] == 'js') {
    header('content-type', 'text/javascript');
    $debugbarRenderer->dumpJsAssets();
}
