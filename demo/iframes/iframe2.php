<?php

include '../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../resources');

$debugbar['messages']->addMessage('I\'m a Deeper Hidden Iframe');

render_demo_page(function () {
    ?>
<script type="text/javascript">
    setTimeout(()=>
        fetch('../ajax.php')
            .then(response => response.text())
            .then(data => {
                //ajax from IFRAME
            }),
        100
    )
</script>
<?php
});
