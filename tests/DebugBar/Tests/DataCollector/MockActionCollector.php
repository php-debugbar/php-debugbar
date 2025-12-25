<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\Actionable;

class MockActionCollector extends MockCollector implements Actionable
{

    function executionAction(string $action, ?array $payload = null): mixed
    {
        return ['result' => 'done'];
    }
}
