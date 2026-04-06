<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;

/**
 * Browser tests for the HTML VarDumper (HtmlDataFormatter).
 *
 * Verifies that Symfony's HtmlDumper output renders correctly
 * with Sfdump JS handling expand/collapse.
 */
class HtmlVarDumperTest extends AbstractBrowserTestCase
{
    private function openMessages(Client $client): \Symfony\Component\Panther\DomCrawler\Crawler
    {
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));

        $crawler = $client->request('GET', '/?formatter=html');
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        return $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');
    }

    public function testRendersAsSfDump(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $dumps = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump');
        $this->assertGreaterThan(0, $dumps->count(), 'HTML dumps should render as <pre class="sf-dump">');
    }

    public function testHasToggleArrows(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $toggles = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump a.sf-dump-toggle');
        $this->assertGreaterThan(0, $toggles->count(), 'HTML dumps should have toggle arrows');
    }

    public function testExpandOnClick(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $toggle = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump a.sf-dump-toggle')->first();
        $toggle->click();
        usleep(500);

        $expanded = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump samp.sf-dump-expanded');
        $this->assertGreaterThan(0, $expanded->count(), 'Clicking toggle should expand content');
    }

    public function testCollapseOnClick(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $toggle = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump a.sf-dump-toggle')->first();
        $toggle->click();
        usleep(500);
        $toggle->click();
        usleep(500);

        $compact = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump samp.sf-dump-compact');
        $this->assertGreaterThan(0, $compact->count(), 'Clicking toggle again should collapse content');
    }

    public function testStringMessages(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(function ($node): string {
                return $node->getText();
            });

        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Hello World!', $messages[0]);
    }

    public function testXssEscaping(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $scripts = $crawler->filter('.phpdebugbar-panel[data-collector=messages] script');
        $this->assertEquals(0, $scripts->count(), 'No <script> elements should be injected');
    }
}
