<?php

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;

class DebugbarTest extends AbstractBrowserTest
{
    public function testDebugbarTab(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/demo/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function(WebDriverElement $node){
                return $node->getText();
            });

        $this->assertEquals('hello', $messages[0]);
        $this->assertCount(4, $messages);

        // Close it again
        $client->click($this->getTabLink($crawler, 'messages'));
        $client->waitForInvisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');

        $client->takeScreenshot(__DIR__ .'/../../../screenshots/minimized.png');
    }

    public function testDebugbarLightMode(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/demo/?theme=light');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $client->takeScreenshot(__DIR__ .'/../../../screenshots/light.png');
    }

    public function testDebugbarDarkMode(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/demo/?theme=dark');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $client->takeScreenshot(__DIR__ .'/../../../screenshots/dark.png');
    }

    public function testDebugbarAjax(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/demo/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-widgets-messages .phpdebugbar-widgets-list');

        $crawler->selectLink('load ajax content')->click();
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=messages]', 'hello from ajax');
        $client->waitForElementToContain('.phpdebugbar-datasets-switcher', 'ajax.php');

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function(WebDriverElement $node){
                return $node->getText();
            });

        $this->assertEquals('hello from ajax', $messages[0]);

        $crawler->selectLink('load ajax content with exception')->click();

        $client->click($this->getTabLink($crawler, 'exceptions'));

        $client->waitForElementToContain('.phpdebugbar-datasets-switcher', 'ajax_exception.php');
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=exceptions] .phpdebugbar-widgets-message', 'Something failed!');

        // Open network tab
        $client->click($this->getTabLink($crawler, '__datasets'));
        $client->waitForVisibility('.phpdebugbar-panel[data-collector=__datasets] .phpdebugbar-widgets-table-row');

        $requests = $crawler->filter('.phpdebugbar-panel[data-collector=__datasets] .phpdebugbar-widgets-table-row')
            ->each(function(WebDriverElement $node){
                return $node->getText();
            });
        $this->assertStringContainsString('GET /demo/', $requests[0]);
        $this->assertStringContainsString('GET /demo/ajax.php (ajax)', $requests[1]);
        $this->assertStringContainsString('GET /demo/ajax_exception.php (ajax)', $requests[2]);

        $client->takeScreenshot(__DIR__ .'/../../../screenshots/ajax.png');
    }

}