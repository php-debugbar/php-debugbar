<?php

include __DIR__ . '/../vendor/autoload.php';

// for stack data
session_start();

use DebugBar\Bridge\Symfony\SymfonyMailCollector;
use DebugBar\DataCollector\HttpCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\TemplateCollector;
use DebugBar\StandardDebugBar;
use DebugBar\Storage\SqliteStorage;

$debugbar = new StandardDebugBar();
/** @var \DebugBar\DataCollector\TimeDataCollector $timeCollector */
$timeCollector = $debugbar['time'];

$debugbar->addCollector(new PdoCollector());
$debugbar->addCollector(new TemplateCollector());
$debugbar->addCollector(new HttpCollector());
$debugbar->addCollector(new SymfonyMailCollector());

// Apply TimeCollector to available collectors
foreach ($debugbar->getCollectors() as $collector) {
    if (method_exists($collector, 'setTimeDataCollector')) {
        $collector->setTimeDataCollector($timeCollector);
    }
}

$debugbarRenderer = $debugbar->getJavascriptRenderer()
                             ->setAssetHandlerUrl('assets.php')
                             ->setAjaxHandlerEnableTab(true)
                             ->setHideEmptyTabs(true)
                             ->setUseDistFiles(false)
                             ->setIncludeVendors(true)
                             ->setCspNonce('demo')
                             ->setTheme($_GET['theme'] ?? 'auto');

//
// create a writable profiles folder in the demo directory to uncomment the following lines
//
$debugbar->setStorage(new DebugBar\Storage\FileStorage(__DIR__ . '/profiles'));

// $debugbar->setStorage(new DebugBar\Storage\RedisStorage(new Predis\Client()));

//$debugbar->setStorage($storage = new SQliteStorage(
//    filepath: __DIR__ . '/../debugbar.sqlite',
//    tableName: 'phpdebugbar',
//));

$debugbarRenderer->setOpenHandlerUrl('open.php');

// configs
// $debugbar->useHtmlVarDumper();
// $debugbar->setEditor('vscode');
// $debugbar->setEditor('vscode');
// $debugbar->setRemoteReplacements(['/remote/demo/' => '/home/demo/']);
// $debugbar['messages']->collectFileTrace();
// $debugbar['time']->showMemoryUsage();

function render_demo_page(?Closure $callback = null)
{
    global $debugbarRenderer;
    ?>
<html>
    <head>
        <script type="text/javascript" nonce="demo">
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.ajax').forEach(function(el) {
                    el.addEventListener('click', function(event) {
                        event.preventDefault();
                        fetch(this.href)
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('ajax-result').innerHTML = data;
                            });
                    });
                });
            });
        </script>
    </head>
    <body>
        <h1>DebugBar Demo</h1>
        <p>DebugBar at the bottom of the page</p>
        <?php if ($callback) {
            $callback();
        } ?>
        <!-- DebugBar Widget Start -->
        <?php
            echo $debugbarRenderer->renderHead();
    echo $debugbarRenderer->render();
    ?>
        <!-- DebugBar Widget End -->
    </body>
</html>
<?php
}
