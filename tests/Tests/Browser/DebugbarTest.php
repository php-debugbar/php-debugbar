<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;

class DebugbarTest extends AbstractBrowserTestCase
{
    public function testDebugbarTab(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/');

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

        $items = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list-item');
        $this->assertCount(9, $items);

        // Close it again
        $client->click($this->getTabLink($crawler, 'messages'));
        $client->waitForInvisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');

        $client->takeScreenshot(__DIR__ . '/../../screenshots/minimized.png');
    }

    public function testDebugbarLightMode(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/?theme=light');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $this->assertEquals(1, $crawler->filter('.phpdebugbar[data-theme="light"]')->count());

        $client->takeScreenshot(__DIR__ . '/../../screenshots/light.png');
    }

    public function testDebugbarDarkMode(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/?theme=dark');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');

        usleep(1000);
        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $this->assertEquals(1, $crawler->filter('.phpdebugbar[data-theme="dark"]')->count());

        $client->takeScreenshot(__DIR__ . '/../../screenshots/dark.png');
    }

    public function testDebugbarAjax(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-widgets-messages .phpdebugbar-widgets-list');

        $crawler->selectLink('load content with fetch()')->click();
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=messages]', 'hello from ajax');
        $client->waitForElementToContain('.phpdebugbar-widgets-datasets-badge', 'ajax.php');

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function (WebDriverElement $node): string {
                return $node->getText();
            });

        $this->assertEquals('hello from ajax', $messages[0]);

        $crawler->selectLink('load ajax content with exception')->click();

        $client->waitForVisibility('.phpdebugbar-tab[data-collector="exceptions"]');

        $client->click($this->getTabLink($crawler, 'exceptions'));

        $client->waitForElementToContain('.phpdebugbar-widgets-datasets-badge', 'ajax_exception.php');
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=exceptions] .phpdebugbar-widgets-message', 'Something failed!');

        // Verify the dataset badge shows count
        $badge = $crawler->filter('.phpdebugbar-widgets-datasets-badge');
        $this->assertStringContainsString('3', $badge->text()); // Should show 3 requests

        // Click on badge to open request list panel
        $badge->click();
        $client->waitForVisibility('.phpdebugbar-widgets-datasets-panel');

        // Verify all 3 requests are in the list
        $requests = $crawler->filter('.phpdebugbar-widgets-datasets-list-item:not([hidden])')
            ->each(function (WebDriverElement $node): string {
                return $node->getText();
            });
        $this->assertCount(3, $requests);
        $this->assertStringContainsString('/', $requests[2]); // First request (bottom of list)
        $this->assertStringContainsString('ajax.php', $requests[1]);
        $this->assertStringContainsString('ajax_exception.php', $requests[0]); // Most recent (top of list)

        $client->takeScreenshot(__DIR__ . '/../../screenshots/ajax.png');
    }

    public function testDebugbarXhr(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-widgets-messages .phpdebugbar-widgets-list');

        $crawler->selectLink('load content with an XMLHttpRequest')->click();
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=messages]', 'hello from ajax');
        $client->waitForElementToContain('.phpdebugbar-widgets-datasets-badge', 'ajax.php');

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function (WebDriverElement $node): string {
                return $node->getText();
            });

        $this->assertEquals('hello from ajax', $messages[0]);
    }

    public function testPdoCollector(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $client->request('GET', '/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'database')) {
            $client->click($this->getTabLink($crawler, 'database'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=database]');

        $client->waitForElementToContain('.phpdebugbar-widgets-sqlqueries .phpdebugbar-widgets-status', '8 statements were executed, 2 of which were duplicates, 6 unique.');

        $statements = $crawler->filter('.phpdebugbar-panel[data-collector=database] li:not([hidden]) .phpdebugbar-widgets-sql')
            ->each(function ($node): string {
                return $node->getText();
            });

        $this->assertEquals("insert into users (name) values ('foo')", $statements[1]);
        $this->assertCount(8, $statements);

        $crawler->selectLink('Show only duplicated')->click();

        $statements = $crawler->filter('.phpdebugbar-panel[data-collector=database] li:not([hidden]) .phpdebugbar-widgets-sql')
            ->each(function ($node): string {
                return $node->getText();
            });

        $this->assertEquals("select * from users where name='foo'", $statements[1]);
        $this->assertCount(2, $statements);

        $crawler->selectLink('Show All')->click();

        $statements = $crawler->filter('.phpdebugbar-panel[data-collector=database] li:not([hidden]) .phpdebugbar-widgets-sql')
            ->each(function ($node): string {
                return $node->getText();
            });

        $this->assertCount(8, $statements);

        $client->takeScreenshot(__DIR__ . '/../../screenshots/pdo.png');

    }

    public function testIframe(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $client->request('GET', '/iframes/index.php');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-widgets-messages .phpdebugbar-widgets-list');

        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=messages]', "I'm a Deeper Hidden Iframe");
        $client->waitForElementToContain('.phpdebugbar-widgets-datasets-badge', 'iframes/iframe2.php');

//        $this->assertEquals(1, $crawler->filter('iframe .phpdebugbar[hidden]')->count());

        $client->takeScreenshot(__DIR__ . '/../../screenshots/iframe.png');

    }
}
