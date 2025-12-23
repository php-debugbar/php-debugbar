<?php

namespace DebugBar\Tests\Browser;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Link;
use Symfony\Component\Panther\PantherTestCase;

/** @internal */
abstract class AbstractBrowserTestCase extends PantherTestCase
{
    public function isTabActive(Crawler $crawler, $tab)
    {
        $node = $crawler->filter('a.phpdebugbar-tab[data-collector="' . $tab . '"]');

        return str_contains($node->attr('class'), 'phpdebugbar-active')  ;
    }

    public function getTabLink(Crawler $crawler, $tab): Link|\Symfony\Component\DomCrawler\Link
    {
        return $crawler->filter('a.phpdebugbar-tab[data-collector="' . $tab . '"]')->link();
    }
}
