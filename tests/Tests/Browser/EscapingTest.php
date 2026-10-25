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
}
