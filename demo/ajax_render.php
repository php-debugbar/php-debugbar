<?php

include 'bootstrap.php';

/** @var \DebugBar\DebugBar|array{messages: MessagesCollector} $debugbar */


$debugbar['messages']->addMessage('hello from rendered ajax');

?>
hello from AJAX

<?= $debugbar->getJavascriptRenderer()->render(false); ?>
