<?php

declare(strict_types=1);

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;

/** @var \DebugBar\DebugBar $debugbar */

$pdo = new TraceablePDO(new PDO('sqlite::memory:'));
$pdoRead = new PDO('sqlite::memory:');
$pdoCollector = new PDOCollector($pdo);
$pdoCollector->enableBacktrace();
$pdoCollector->setDurationBackground(true);
$pdoCollector->setRenderSqlWithParams();
$pdoCollector->addConnection($pdoRead, 'read');
$debugbar->addCollector($pdoCollector);

$pdo->exec('create table users (id integer, name varchar, email varchar)');
$pdoRead->exec('create table users (id integer, name varchar, email varchar)');

$stmt = $pdo->prepare('insert into users (name) values (?)');
$stmt->execute(['foo']);
$stmt->execute(['bar']);

$users = $pdo->query('select * from users')->fetchAll();
$stmt = $pdo->prepare('select * from users where name=?');
$stmt->execute(['foo']);
$stmt->execute(['foo']);
$foo = $stmt->fetch();

$stmt = $pdoRead->prepare('select * from users where name=:name and email=:email');
$stmt->execute(['name' => 'Barry', ':email' => '<script>alert();</script>']);
$foo = $stmt->fetch();

$pdo->exec('delete from users');
