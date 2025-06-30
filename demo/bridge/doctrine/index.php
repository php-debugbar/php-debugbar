<?php

include __DIR__ . '/bootstrap.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$debugBarSQLMiddleware = new DebugBar\Bridge\Doctrine\DebugBarSQLMiddleware();
$debugbar->addCollector(new DebugBar\Bridge\Doctrine\DoctrineCollector($debugBarSQLMiddleware));

$config->setMiddlewares([$debugBarSQLMiddleware]);
$entityManager = createEntityManager($config);

$product = new Demo\Product();
$product->setName("foobar");
$product->setUpdated();

$entityManager->persist($product);
$entityManager->flush();
$entityManager->createQuery("select p from  Demo\\Product p where p.updated>:u")->setParameter("u", new \DateTime('1 hour ago'))->execute();
$entityManager->createQuery("select p from  Demo\\Product p where p.name=:c")->setParameter("c", "<script>alert();</script>")->execute();
render_demo_page();
