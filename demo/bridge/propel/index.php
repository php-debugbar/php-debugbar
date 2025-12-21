<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../resources');

use DebugBar\Bridge\PropelCollector;

$debugbar->addCollector(new PropelCollector());

Propel::init('build/conf/demo-conf.php');
set_include_path("build/classes" . PATH_SEPARATOR . get_include_path());

PropelCollector::enablePropelProfiling();

$user = new User();
$user->setName('foo');
$user->save();

$firstUser = UserQuery::create()->findPK(1);

render_demo_page();
