<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\Actionable;

class MockActionCollector extends MockCollector implements Actionable
{

    function executionAction($action, array $payload = null)
    {
        return ['result' => 'done'];
    }
}
