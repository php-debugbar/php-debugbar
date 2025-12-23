<?php

namespace DebugBar\Tests\Browser\Bridge;

use DebugBar\Tests\Browser\AbstractBrowserTestCase;

class MonologTest extends AbstractBrowserTestCase
{
    public function testMonologCollector(): void
    {
        if (!file_exists(__DIR__ . '/../../../../../demo/bridge/monolog/vendor/autoload.php')) {
            $this->markTestSkipped('Monolog is not installed');
        }

        $client = static::createPantherClient();

        $client->request('GET', '/demo/bridge/monolog');

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
