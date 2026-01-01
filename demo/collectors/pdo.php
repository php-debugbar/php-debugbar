<?php

declare(strict_types=1);

use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PDO\PDOCollector;

/** @var \DebugBar\DebugBar $debugbar */

$pdo = new TraceablePDO(new PDO('sqlite::memory:'));
$pdoCollector = new PDOCollector($pdo);
$debugbar->addCollector($pdoCollector);
$pdoCollector->setDurationBackground(true);
$pdoCollector->setRenderSqlWithParams();

$pdo->exec('create table users (name varchar)');
$stmt = $pdo->prepare('insert into users (name) values (?)');
$stmt->execute(['foo']);
$stmt->execute(['bar']);

$users = $pdo->query('select * from users')->fetchAll();
$stmt = $pdo->prepare('select * from users where name=?');
$stmt->execute(['foo']);
$stmt->execute(['foo']);
$foo = $stmt->fetch();

$stmt = $pdo->prepare('select * from users where name=?');
$stmt->execute(['<script>alert();</script>']);
$foo = $stmt->fetch();

$pdo->exec('delete from users');
