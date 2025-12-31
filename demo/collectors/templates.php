<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

$templateCollector = new \DebugBar\DataCollector\TemplateCollector();
$debugbar->addCollector($templateCollector);

$templateCollector->addTemplate('index.php', ['foo' => 'bar'], 'php', __FILE__);
$templateCollector->addTemplate('docs.php', ['demo' => 'true'], 'php', __FILE__);
