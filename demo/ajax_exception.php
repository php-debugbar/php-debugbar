<?php

include 'bootstrap.php';

try {
    throw new Exception('Something failed!');
} catch (Exception $e) {
    $debugbar['exceptions']->addException($e);
}

?>
error from AJAX
<?php
    $debugbar->sendDataInHeaders(true);
?>
