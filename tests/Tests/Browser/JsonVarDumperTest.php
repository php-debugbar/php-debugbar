<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;

/**
 * Browser tests for the JSON VarDumper (JsonDataFormatter).
 *
 * Verifies that the _vd format renders correctly as interactive trees.
 */
class JsonVarDumperTest extends AbstractBrowserTestCase
{
    private function openMessages($client): \Symfony\Component\DomCrawler\Crawler
    {
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/?formatter=json');
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        return $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');
    }

    public function testRendersAsPreElements(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $dumps = $crawler->filter('.phpdebugbar-panel[data-collector=messages] pre.sf-dump');
        $this->assertGreaterThan(0, $dumps->count(), 'Dumps should render as <pre class="sf-dump">');
    }

    public function testCollapsedShowsPreview(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $previews = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .sf-dump-preview');
        $this->assertGreaterThan(0, $previews->count(), 'Collapsed dumps should show preview text');
    }

    public function testExpandOnClick(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $toggle = $crawler->filter('.phpdebugbar-panel[data-collector=messages] a.sf-dump-toggle')->first();
        $this->assertGreaterThan(0, $toggle->count(), 'Should have at least one toggle');

        $toggle->click();
        usleep(500);

        $expanded = $crawler->filter('.phpdebugbar-panel[data-collector=messages] samp.sf-dump-expanded');
        $this->assertGreaterThan(0, $expanded->count(), 'Clicking toggle should expand the node');
    }

    public function testCollapseOnClick(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $toggle = $crawler->filter('.phpdebugbar-panel[data-collector=messages] a.sf-dump-toggle')->first();
        $toggle->click();
        usleep(500);

        $toggle->click();
        usleep(500);

        $compact = $crawler->filter('.phpdebugbar-panel[data-collector=messages] samp.sf-dump-compact');
        $this->assertGreaterThan(0, $compact->count(), 'Clicking toggle again should collapse the node');
    }

    public function testObjectShowsClassName(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $notes = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .sf-dump-note');
        $noteTexts = $notes->each(fn(WebDriverElement $n): string => $n->getText());

        $hasClassName = false;
        foreach ($noteTexts as $text) {
            if (str_contains($text, 'DebugBar') || str_contains($text, 'array:')) {
                $hasClassName = true;
                break;
            }
        }
        $this->assertTrue($hasClassName, 'Should show class names or array counts');
    }

    public function testObjectShowsRefHandle(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $refs = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .sf-dump-ref');
        $this->assertGreaterThan(0, $refs->count(), 'Objects should show reference handles (#N)');
    }

    public function testStringMessages(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $messages = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-value')
            ->each(fn(WebDriverElement $n): string => $n->getText());

        $this->assertNotEmpty($messages);
        $this->assertEquals('Hello World!', $messages[0]);
    }

    public function testXssEscaping(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $scripts = $crawler->filter('.phpdebugbar-panel[data-collector=messages] script');
        $this->assertEquals(0, $scripts->count(), 'No <script> elements should be injected by dump content');
    }

    public function testContextRendering(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $contextCounts = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-context-count');
        $this->assertGreaterThan(0, $contextCounts->count(), 'Messages with context should show context count badge');
    }

    public function testRequestDataTab(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/?formatter=json');
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        $client->click($this->getTabLink($crawler, 'request'));
        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=request]');

        $kvItems = $crawler->filter('.phpdebugbar-panel[data-collector=request] .phpdebugbar-widgets-kvlist dt');
        $this->assertGreaterThan(0, $kvItems->count(), 'Request tab should show key-value data');
    }

    public function testExceptionsTab(): void
    {
        $client = static::createPantherClient();
        $size = new WebDriverDimension(1920, 800);
        $client->manage()->window()->setSize($size);

        $crawler = $client->request('GET', '/?formatter=json');
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        $crawler->selectLink('load ajax content with exception')->click();
        $client->waitForVisibility('.phpdebugbar-tab[data-collector="exceptions"]');

        $client->click($this->getTabLink($crawler, 'exceptions'));
        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=exceptions]', 'Something failed!');

        $exceptionText = $crawler->filter('.phpdebugbar-panel[data-collector=exceptions] .phpdebugbar-widgets-message')->getText();
        $this->assertStringContainsString('Something failed!', $exceptionText);

        $traces = $crawler->filter('.phpdebugbar-panel[data-collector=exceptions] pre.sf-dump');
        $this->assertGreaterThan(0, $traces->count(), 'Stack traces should render as dump trees');
    }
}
