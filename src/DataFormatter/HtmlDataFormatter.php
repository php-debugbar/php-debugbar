<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataFormatter\VarDumper\DebugBarHtmlDumper;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Clones and renders variables in HTML format using the Symfony VarDumper component.
 *
 * Cloning is decoupled from rendering, so that dumper users can have the fastest possible cloning
 * performance, while delaying rendering until it is actually needed.
 */
class HtmlDataFormatter extends DataFormatter implements AssetProvider
{
    protected static array $defaultDumperOptions = [
        'expanded_depth' => 1,
        'styles' => [
            // NOTE:  'default' CSS is also specified in debugbar.css
            'default' => 'word-wrap: break-word; white-space: pre-wrap; word-break: normal',
            'num' => 'font-weight:bold; color:#1299DA',
            'const' => 'font-weight:bold',
            'str' => 'font-weight:bold; color:#3A9B26',
            'note' => 'color:#1299DA',
            'ref' => 'color:#7B7B7B',
            'public' => 'color:#000000',
            'protected' => 'color:#000000',
            'private' => 'color:#000000',
            'meta' => 'color:#B729D9',
            'key' => 'color:#3A9B26',
            'index' => 'color:#1299DA',
            'ellipsis' => 'color:#A0A000',
        ],
    ];
    protected ?array $dumperOptions = null;

    protected function dumpClonedVar(Data $data): string
    {
        $dumper = $this->getDumper();
        if ($dumper instanceof HtmlDumper) {
            $dumper->setDumpHeader('');
            return $dumper->dump($data, true, $this->getDisplayOptions());
        }
        return parent::dumpClonedVar($data);

    }
    /**
     * Gets the DebugBarHtmlDumper instance with configuration options set.
     *
     */
    protected function getDumper(): DataDumperInterface
    {
        if (!$this->dumper) {
            $this->dumper = new DebugBarHtmlDumper();
            $this->dumper->setDumpBoundaries('<pre class=sf-dump id=%s data-indent-pad="%s">', '</pre>');
            $dumperOptions = $this->getDumperOptions();
            if (isset($dumperOptions['styles'])) {
                $this->dumper->setStyles($dumperOptions['styles']);
            }
            $this->dumper->setDumpHeader('');
        }
        return $this->dumper;
    }

    /**
     * Gets the array of non-default HtmlDumper configuration options.
     *
     */
    public function getDumperOptions(): array
    {
        if ($this->dumperOptions === null) {
            $this->dumperOptions = static::$defaultDumperOptions;
        }
        return $this->dumperOptions;
    }

    /**
     * Merges an array of non-default HtmlDumper configuration options with the existing non-default
     * options.
     *
     * Configuration options are:
     *  - styles: a map of CSS styles to include in the assets, as documented in
     *    HtmlDumper::setStyles.
     *  - expanded_depth: the tree depth to initially expand.
     *  - max_string: maximum string size.
     *  - file_link_format: link format for files; %f expanded to file and %l expanded to line
     *
     */
    public function mergeDumperOptions(array $options): void
    {
        $this->dumperOptions = $options + $this->getDumperOptions();
        $this->dumper = null;
    }

    /**
     * Resets the array of non-default HtmlDumper configuration options without retaining any of the
     * existing non-default options.
     *
     * Configuration options are:
     *  - styles: a map of CSS styles to include in the assets, as documented in
     *    HtmlDumper::setStyles.
     *  - expanded_depth: the tree depth to initially expand.
     *    (Requires Symfony 3.2; ignored on older versions.)
     *  - max_string: maximum string size.
     *    (Requires Symfony 3.2; ignored on older versions.)
     *  - file_link_format: link format for files; %f expanded to file and %l expanded to line
     *    (Requires Symfony 3.2; ignored on older versions.)
     *
     */
    public function resetDumperOptions(?array $options = null): void
    {
        $this->dumperOptions = ($options ?: []) + static::$defaultDumperOptions;
        $this->dumper = null;
    }

    /**
     * Gets the display options for the HTML dumper.
     *
     */
    protected function getDisplayOptions(): array
    {
        $displayOptions = [];
        $dumperOptions = $this->getDumperOptions();
        if (isset($dumperOptions['expanded_depth'])) {
            $displayOptions['maxDepth'] = $dumperOptions['expanded_depth'];
        }
        if (isset($dumperOptions['max_string'])) {
            $displayOptions['maxStringLength'] = $dumperOptions['max_string'];
        }
        if (isset($dumperOptions['file_link_format'])) {
            $displayOptions['fileLinkFormat'] = $dumperOptions['file_link_format'];
        }
        return $displayOptions;
    }

    /**
     * Returns assets required for rendering variables.
     *
     */
    public function getAssets(): array
    {
        $dumper = $this->getDumper();
        if ($dumper instanceof DebugBarHtmlDumper) {
            $dumper->resetDumpHeader(); // this will cause the default dump header to regenerate
            return [
                'inline_head' => [
                    'html_var_dumper' => $dumper->getDumpHeaderByDebugBar(),
                ],
            ];
        }

        return [];
    }
}
