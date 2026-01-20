<?php

include '../bootstrap.php';

/** @var \DebugBar\DebugBar $debugbar */
/** @var \DebugBar\JavascriptRenderer $debugbarRenderer */

$debugbarRenderer->setAssetHandlerUrl('../assets.php')
    ->setOpenHandlerUrl('../open.php');

$debugbar['messages']->addMessage('I\'m a Deeper Hidden Iframe');

render_demo_page(function () {
    ?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            fetch('../ajax.php')
                .then(response => response.text())
                .then(data => {
                    //ajax from IFRAME
                });
        }, 250);
    });
</script>
<?php
});
