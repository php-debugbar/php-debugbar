<?php

declare(strict_types=1);

include 'bootstrap.php';
$debugbar['messages']->addMessage('hello from redirect');
$debugbar->stackData();
header('Location: index.php');
