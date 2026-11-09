<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;

/**
 * An SPA navigation (Livewire wire:navigate, Turbo, htmx boost) swaps in a new body that carries the
 * initialization script again, so a second DebugBar is built in the same window. The previous one must
 * stop listening on window and document, or those listeners keep it and the previous page alive.
 */
class ReinitializeTest extends AbstractBrowserTestCase
{
    /**
     * Loads the demo, then runs its initialization script a second time the way an SPA navigation
     * does, after taking the first toolbar off the page.
     */
    private function reinitialize(Client $client): void
    {
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));
        $client->request('GET', '/');
        $client->waitFor('.phpdebugbar-body');

        $client->executeScript(<<<'JS'
            const previous = window.PhpDebugBar.instance;
            window.previousDebugBar = previous;
            previous.el.remove();

            const init = [...document.scripts].find((script) => script.textContent.includes('renderDebugbar'));
            const again = document.createElement('script');
            again.textContent = init.textContent;
            document.body.append(again);
        JS);

        $client->waitFor('.phpdebugbar-body');
    }

    public function testBuildsANewInstance(): void
    {
        $client = static::createPantherClient();
        $this->reinitialize($client);

        $this->assertFalse($client->executeScript('return window.PhpDebugBar.instance === window.previousDebugBar;'));
    }

    public function testPreviousInstanceIgnoresNavigationEvents(): void
    {
        $client = static::createPantherClient();
        $this->reinitialize($client);

        $calls = $client->executeScript(<<<'JS'
            let calls = 0;
            window.previousDebugBar.recalculateBodyPadding = () => calls++;
            document.dispatchEvent(new Event('livewire:navigated'));
            return calls;
        JS);

        $this->assertSame(0, $calls);
    }

    public function testPreviousInstanceIgnoresWindowResize(): void
    {
        $client = static::createPantherClient();
        $this->reinitialize($client);

        // resize() reads the header on every call while respCSSSize is 0, so counting reads counts calls.
        $calls = $client->executeScript(<<<'JS'
            const previous = window.previousDebugBar;
            const header = previous.header;
            let calls = 0;
            previous.respCSSSize = 0;
            Object.defineProperty(previous, 'header', { get() { calls++; return header; } });
            window.dispatchEvent(new Event('resize'));
            return calls;
        JS);

        $this->assertSame(0, $calls);
    }

    public function testPreviousDatasetSwitcherIgnoresDocumentClicks(): void
    {
        $client = static::createPantherClient();
        $this->reinitialize($client);

        $hidden = $client->executeScript(<<<'JS'
            const panel = window.previousDebugBar.datasetSwitcherWidget.panel;
            panel.hidden = false;
            document.body.click();
            return panel.hidden;
        JS);

        $this->assertFalse($hidden);
    }
}
