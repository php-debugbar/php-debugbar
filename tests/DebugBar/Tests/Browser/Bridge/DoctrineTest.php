<?php

namespace DebugBar\Tests\Browser\Bridge;

use DebugBar\Browser\Bridge\WebDriverElement;
use DebugBar\Tests\Browser\AbstractBrowserTest;

class DoctrineTest extends AbstractBrowserTest
{
    public function testMonologCollector(): void
    {
        if (!file_exists(__DIR__ . '/../../../../../demo/bridge/doctrine/vendor/autoload.php')) {
            $this->markTestSkipped('Doctrine is not installed');
        }

        $client = static::createPantherClient();

        $client->request('GET', '/demo/bridge/doctrine');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'database')) {
            $client->click($this->getTabLink($crawler, 'database'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=database]');

        $statements = $crawler->filter('.phpdebugbar-panel[data-collector=database] .phpdebugbar-widgets-sql')
            ->each(function($node){
                return $node->getText();
            });

        $this->assertEquals('INSERT INTO products (name, updated) VALUES (?, ?)', $statements[1]);
        $this->assertCount(5, $statements);
    }

}