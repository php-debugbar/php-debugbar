<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */
/** @var \DebugBar\JavascriptRenderer $debugbarRenderer */

//Disable session caching
session_cache_limiter('');

include 'bootstrap.php';

$openHandler = new DebugBar\AssetHandler($debugbar);
$openHandler->handle($_GET);
