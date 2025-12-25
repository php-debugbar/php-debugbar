<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\Actionable;

class MockActionCollector extends MockCollector implements Actionable
{
    public function executionAction(string $action, ?array $payload = null): mixed
    {
        return ['result' => 'done'];
    }
}
