<?php

use DebugBar\DataCollector\MessagesCollector;


include 'bootstrap.php';

/** @var \DebugBar\DebugBar|array{messages: MessagesCollector} $debugbar */

$debugbar['messages']->addMessage('Hello from redirected AJAX');

$debugbar->stackData();

header('Location: ajax.php');

