<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;

/**
 * Browser tests for the base DataFormatter.
 *
 * Verifies that plain text messages render with truncation
 * and toggle between single-line and pretty-printed views.
 */
class BaseFormatterTest extends AbstractBrowserTestCase
{
    private function openMessages(Client $client): \Symfony\Component\Panther\DomCrawler\Crawler
    {
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));

        $crawler = $client->request('GET', '/?formatter=base');
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        return $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');
    }

    public function testShowsTruncatedLine(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $truncated = $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-truncated');
        $this->assertGreaterThan(0, $truncated->count(), 'Base formatter should show truncated message lines');
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

    public function testLongStringTogglePretty(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->openMessages($client);

        $client->waitForElementToContain('.phpdebugbar-panel[data-collector=messages]', 'Lorem ipsum');

        // Find the truncated Lorem ipsum value
        /** @var \Facebook\WebDriver\WebDriverElement|null $loremItem */
        $loremItem = null;
        $crawler->filter('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-truncated')
            ->each(function ($el) use (&$loremItem): void {
                if ($loremItem === null && str_contains($el->getText(), 'Lorem ipsum')) {
                    $loremItem = $el;
                }
            });

        $this->assertNotNull($loremItem, 'Should find the Lorem ipsum truncated message');

        // Click the parent list item to toggle to pretty
        $listItem = $loremItem->findElement(WebDriverBy::xpath('..'));
        $listItem->click();
        usleep(500);

        $classes = $loremItem->getAttribute('class');
        $this->assertTrue(
            str_contains($classes, 'phpdebugbar-widgets-pretty'),
            'After click, value should have pretty class'
        );

        // Click again to collapse back
        $listItem->click();
        usleep(500);

        $classes = $loremItem->getAttribute('class');
        $this->assertTrue(
            str_contains($classes, 'phpdebugbar-widgets-truncated'),
            'After second click, value should be truncated again'
        );
    }
}
