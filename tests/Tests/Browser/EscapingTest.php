<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies that collected string values are rendered as text, not HTML, for every formatter.
 */
class EscapingTest extends AbstractBrowserTestCase
{
    public static function formatterProvider(): array
    {
        return [['json'], ['html'], ['base']];
    }

    #[DataProvider('formatterProvider')]
    public function testMessageContextIsEscaped(string $formatter): void
    {
        $client = static::createPantherClient();
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));

        $client->request('GET', '/?formatter=' . $formatter);
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }
        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');

        $panel = '.phpdebugbar-panel[data-collector=messages]';
        $this->assertCount(0, $crawler->filter($panel . ' img.xss-probe'), 'HTML in context must not be rendered');

        $html = $client->executeScript('return document.querySelector(arguments[0]).innerHTML;', [$panel]);
        $this->assertStringContainsString('&lt;img class="xss-probe" src="x"&gt;', $html, 'HTML in context should be shown as text');
    }

    public function testRequestMetaKeysAreNotTrusted(): void
    {
        $client = static::createPantherClient();
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));

        $payload = '<img class="xss-probe" src="x">';
        $client->request('GET', '/?formatter=json&' . http_build_query([
            '_cut' => $payload,
            '_vd' => [0, $payload, $payload],
            'nested' => ['_cut' => $payload, '_vd' => [0, $payload]],
        ]));
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'request')) {
            $client->click($this->getTabLink($crawler, 'request'));
        }
        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=request] .phpdebugbar-widgets-kvlist');

        $panel = '.phpdebugbar-panel[data-collector=request]';
        // Expand all collapsed dumps so lazy children are rendered too
        $client->executeScript('document.querySelectorAll(arguments[0] + " a.sf-dump-toggle").forEach(a => a.dispatchEvent(new MouseEvent("click", {bubbles: true, metaKey: true})));', [$panel]);

        $this->assertCount(0, $crawler->filter($panel . ' img.xss-probe'), 'Request data must not be rendered as HTML');

        $html = $client->executeScript('return document.querySelector(arguments[0]).innerHTML;', [$panel]);
        $this->assertStringContainsString('<span class="sf-dump-key">_cut</span>', $html, 'User _cut key should be shown as a regular key');
        $this->assertStringContainsString('&lt;img class="xss-probe" src="x"&gt;', $html, 'Payload should be shown as text');
    }
}
