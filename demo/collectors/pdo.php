<?php

declare(strict_types=1);

use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PDO\PDOCollector;

/** @var \DebugBar\DebugBar $debugbar */

$pdo = new TraceablePDO(new PDO('sqlite::memory:'));
$debugbar->addCollector(new PDOCollector($pdo));
$debugbar['pdo']->setDurationBackground(true);

$pdo->exec('create table users (name varchar)');
$stmt = $pdo->prepare('insert into users (name) values (?)');
$stmt->execute(['foo']);
$stmt->execute(['bar']);

$users = $pdo->query('select * from users')->fetchAll();
$stmt = $pdo->prepare('select * from users where name=?');
$stmt->execute(['foo']);
$foo = $stmt->fetch();

$stmt = $pdo->prepare('select * from users where name=?');
$stmt->execute(['<script>alert();</script>']);
$foo = $stmt->fetch();

$pdo->exec('delete from users');
