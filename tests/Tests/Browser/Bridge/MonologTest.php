<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser\Bridge;

use DebugBar\Tests\Browser\AbstractBrowserTestCase;

class MonologTest extends AbstractBrowserTestCase
{
    public function testMonologCollector(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'monolog')) {
            $client->click($this->getTabLink($crawler, 'monolog'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=monolog]');

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=monolog] .phpdebugbar-widgets-value')
            ->each(function ($node) {
                return $node->getText();
            });

        $this->assertStringContainsString('demo.INFO: hello world [] []', $messages[0]);
    }

}
