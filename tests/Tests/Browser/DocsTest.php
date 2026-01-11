<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;

class DocsTest extends AbstractBrowserTestCase
{
    public function testDocs(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/');
        $content = file_get_contents($client->getCurrentURL());

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');
        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function (WebDriverElement $node): string {
                return $node->getText();
            });

        $this->assertEquals('Hello World!', $messages[0]);

        // Store output for test
        @mkdir(__DIR__ . '/../../../build/docs', 0o777, true);
        file_put_contents(__DIR__ . '/../../../build/docs/render.html', $content);
    }
}
