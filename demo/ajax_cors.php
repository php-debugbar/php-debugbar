<?php

include 'bootstrap.php';
header('Access-Control-Allow-Origin: *');
if (!empty($_GET['expose'])) {
    header('Access-Control-Expose-Headers: phpdebugbar, phpdebugbar-id, phpdebugbar-stack');
}
$debugbar['messages']->addMessage('hello from cors ajax');
$debugbar->sendDataInHeaders(true);
?>
hello from CORS AJAX
