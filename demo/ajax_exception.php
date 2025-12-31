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

?>
error from AJAX
<?php
    $debugbar->sendDataInHeaders(true);
?>
