<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

use DebugBar\DataCollector\TemplateCollector;

/** @var TemplateCollector $templateCollector */
$templateCollector = $debugbar['templates'];

$templateCollector->addTemplate('index.php', ['foo' => 'bar'], 'php', __FILE__);
$templateCollector->addTemplate('docs.php', ['demo' => 'true'], 'php', __FILE__);
