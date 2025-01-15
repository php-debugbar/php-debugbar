<?php

include __DIR__ . '/../vendor/autoload.php';

// for stack data
session_start();

use DebugBar\StandardDebugBar;

$debugbar = new StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer()
                             ->setBaseUrl('../src/DebugBar/Resources')
                             ->setAjaxHandlerEnableTab(true)
                             ->setHideEmptyTabs(true)
                             ->setEnableJqueryNoConflict(false)
                             ->setTheme($_GET['theme'] ?? 'auto');

//
// create a writable profiles folder in the demo directory to uncomment the following lines
//
// $debugbar->setStorage(new DebugBar\Storage\FileStorage(__DIR__ . '/profiles'));
// $debugbar->setStorage(new DebugBar\Storage\RedisStorage(new Predis\Client()));
// $debugbarRenderer->setOpenHandlerUrl('open.php');

function render_demo_page(?Closure $callback = null)
{
    global $debugbarRenderer;
?>
<html>
    <head>
        <?php echo $debugbarRenderer->renderHead() ?>
        <script type="text/javascript">
            $(function() {
                $('.ajax').click(function() {
                    $.get(this.href, function(data) {
                        $('#ajax-result').html(data);
                    });
                    return false;
                });
            });
        </script>
    </head>
    <body>
        <h1>DebugBar Demo</h1>
        <p>DebugBar at the bottom of the page</p>
        <?php if ($callback) $callback(); ?>
        <?php
            echo $debugbarRenderer->render();
        ?>
    </body>
</html>
<?php
}
