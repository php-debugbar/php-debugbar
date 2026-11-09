<?php

declare(strict_types=1);

namespace DebugBar\Tests\Browser;

use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;

/**
 * An SPA navigation (Livewire wire:navigate, Turbo) evaluates the scripts in the new body again, so
 * the asset bundle (vardumper.js included) runs a second time in the same window and the debugbar is
 * built again. A dump must still expand with one click afterwards.
 */
class JsonVarDumperRerunTest extends AbstractBrowserTestCase
{
    /**
     * Loads the demo, then runs the asset bundle and the initialization script again, the way an SPA
     * navigation does, after taking the first toolbar off the page. Leaves the messages panel open.
     */
    private function rerun(Client $client): \Symfony\Component\Panther\DomCrawler\Crawler
    {
        $client->manage()->window()->setSize(new WebDriverDimension(1920, 800));
        $client->request('GET', '/?formatter=json');
        $client->waitFor('.phpdebugbar-body');

        $client->executeScript(<<<'JS'
            window.PhpDebugBar.instance.el.remove();

            const bundle = [...document.scripts].find((script) => script.src.includes('assets.php') && script.src.includes('js'));
            const init = [...document.scripts].find((script) => script.textContent.includes('renderDebugbar'));

            const bundleAgain = document.createElement('script');
            bundleAgain.src = bundle.src;
            bundleAgain.onload = () => {
                const initAgain = document.createElement('script');
                initAgain.textContent = init.textContent;
                document.body.append(initAgain);
            };
            document.body.append(bundleAgain);
        JS);

        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'messages')) {
            $client->click($this->getTabLink($crawler, 'messages'));
        }

        return $client->waitForVisibility('.phpdebugbar-panel[data-collector=messages] .phpdebugbar-widgets-list');
    }

    public function testExpandOnClickAfterRerun(): void
    {
        $client = static::createPantherClient();
        $crawler = $this->rerun($client);

        // Marks the toggle of the first collapsed node, so the assertion reads that node alone.
        $marked = $client->executeScript(<<<'JS'
            const panel = document.querySelector('.phpdebugbar-panel[data-collector=messages]');
            const toggle = [...panel.querySelectorAll('a.sf-dump-toggle')]
                .find((a) => a.nextElementSibling?.nextElementSibling?.classList.contains('sf-dump-compact'));
            toggle?.setAttribute('data-rerun-toggle', '');
            return !! toggle;
        JS);
        $this->assertTrue($marked, 'The demo should render a collapsed node');

        $crawler->filter('a[data-rerun-toggle]')->click();
        usleep(500);

        $state = $client->executeScript(
            "return document.querySelector('a[data-rerun-toggle]').nextElementSibling.nextElementSibling.className;"
        );
        $this->assertStringContainsString('sf-dump-expanded', $state, 'One click should expand the node after the bundle ran again');
    }
}
