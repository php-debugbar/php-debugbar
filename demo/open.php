<?php

declare(strict_types=1);

include __DIR__ . '/bootstrap.php';

$openHandler = new DebugBar\OpenHandler($debugbar);
$openHandler->handle();
