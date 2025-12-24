<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

$classDemo = ['FirstClass', 'SecondClass', 'ThirdClass'];
$classEvent = ['Retrieved', 'Saved', 'Deleted'];
$debugbar->addCollector(new \DebugBar\DataCollector\ObjectCountCollector());
$debugbar['counter']->collectCountSummary(true);
$debugbar['counter']->setKeyMap($classEvent);
for ($i = 0; $i <= 20; $i++) {
    $debugbar['counter']->countClass($classDemo[rand(0, 2)], 1, $classEvent[rand(0, 2)]);
}
