<?php

include '../bootstrap.php';

/** @var \DebugBar\DebugBar $debugbar */
/** @var \DebugBar\JavascriptRenderer $debugbarRenderer */

$debugbarRenderer->setAssetHandlerUrl('../assets.php');

$debugbar['messages']->addMessage('Top Page(Main debugbar)');

render_demo_page(function () {
    ?>
<iframe src="iframe1.php" height="350" style="width:100%;"></iframe>
<?php
});
