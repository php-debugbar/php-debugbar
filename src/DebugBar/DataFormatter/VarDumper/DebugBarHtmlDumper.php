<?php

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * We have to extend the base HtmlDumper class in order to get access to the protected-only
 * getDumpHeader function.
 */
class DebugBarHtmlDumper extends HtmlDumper
{
    /**
     * Resets an HTML header.
     */
    public function resetDumpHeader()
    {
        $this->dumpHeader = null;
    }

    public function getDumpHeaderByDebugBar() 
    {
        $header = str_replace('pre.sf-dump', '.phpdebugbar pre.sf-dump', $this->getDumpHeader());

        if (isset(self::$themes['dark'])) {
            $line = '';
            foreach (self::$themes['dark'] as $class => $style) {
                $line .= ".phpdebugbar[data-theme='dark'] pre.sf-dump".('default' === $class ? ', pre.sf-dump' : '').' .sf-dump-'.$class.'{'.$style.'}';
            }
            $line .= ".phpdebugbar[data-theme='dark'] " . 'pre.sf-dump .sf-dump-ellipsis-note{'.self::$themes['dark']['note'].'}';
            $header = str_replace('</style>', $line . '</style>', $header);
        }

        return $header;
    }
}
