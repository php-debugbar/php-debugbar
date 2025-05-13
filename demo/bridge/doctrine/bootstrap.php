<?php
// bootstrap.php

require_once "vendor/autoload.php";

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = ORMSetup::createAttributeMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
// or if you prefer yaml or XML
//$config = ORMSetup::createAttributeMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = ORMSetup::createAttributeMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// obtaining the entity manager
function createEntityManager($config) {
    $db = [
        'driver' => 'pdo_sqlite',
        'path'   => __DIR__ . '/db.sqlite',
    ];
    $conn = DriverManager::getConnection($db, $config);

    return new EntityManager($conn, $config);
}
