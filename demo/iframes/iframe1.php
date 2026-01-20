<?php

include '../bootstrap.php';

/** @var \DebugBar\DebugBar $debugbar */
/** @var \DebugBar\JavascriptRenderer $debugbarRenderer */

$debugbarRenderer->setAssetHandlerUrl('../assets.php');

$debugbar['messages']->addMessage('I\'m a IFRAME');

render_demo_page(function () {
    ?>
<iframe src="iframe2.php" style="display:none;"></iframe>
<?php
});
