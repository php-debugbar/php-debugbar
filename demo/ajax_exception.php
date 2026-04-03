<?php

include 'bootstrap.php';

function doSomething()
{
    throw new Exception('Something failed!');
}
try {
    doSomething();
} catch (Exception $e) {
    $debugbar['exceptions']->addException($e);
}
try {
    doSomething();
} catch (Exception $e) {
    $debugbar['exceptions']->addException($e);
}
http_response_code(500);
$debugbar->sendDataInHeaders(true);

?>
error from AJAX
