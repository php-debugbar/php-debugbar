<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataFormatter\VarDumper\DebugBarHtmlDumper;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * Clones and renders variables in HTML format using the Symfony VarDumper component.
 *
 * Cloning is decoupled from rendering, so that dumper users can have the fastest possible cloning
 * performance, while delaying rendering until it is actually needed.
 */
class DebugBarVarDumper
{
    protected static array $defaultClonerOptions = [];

    protected static array $defaultDumperOptions = [
        'expanded_depth' => 0,
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

    protected ?array $clonerOptions = null;

    protected ?array $dumperOptions = null;

    protected ?VarCloner $cloner = null;

    protected ?DebugBarHtmlDumper $dumper = null;

    /**
     * Gets the VarCloner instance with configuration options set.
     *
     * @return VarCloner
     */
    protected function getCloner()
    {
        if (!$this->cloner) {
            $clonerOptions = $this->getClonerOptions();
            if (isset($clonerOptions['casters'])) {
                $this->cloner = new VarCloner($clonerOptions['casters']);
            } else {
                $this->cloner = new VarCloner();
            }
            if (isset($clonerOptions['additional_casters'])) {
                $this->cloner->addCasters($clonerOptions['additional_casters']);
            }
            if (isset($clonerOptions['max_items'])) {
                $this->cloner->setMaxItems($clonerOptions['max_items']);
            }
            if (isset($clonerOptions['max_string'])) {
                $this->cloner->setMaxString($clonerOptions['max_string']);
            }
            if (isset($clonerOptions['min_depth'])) {
                $this->cloner->setMinDepth($clonerOptions['min_depth']);
            }
        }
        return $this->cloner;
    }

    /**
     * Gets the DebugBarHtmlDumper instance with configuration options set.
     *
     * @return DebugBarHtmlDumper
     */
    protected function getDumper()
    {
        if (!$this->dumper) {
            $this->dumper = new DebugBarHtmlDumper();
            $dumperOptions = $this->getDumperOptions();
            if (isset($dumperOptions['styles'])) {
                $this->dumper->setStyles($dumperOptions['styles']);
            }
        }
        return $this->dumper;
    }

    /**
     * Gets the array of non-default VarCloner configuration options.
     *
     */
    public function getClonerOptions(): array
    {
        if ($this->clonerOptions === null) {
            $this->clonerOptions = self::$defaultClonerOptions;
        }
        return $this->clonerOptions;
    }

    /**
     * Merges an array of non-default VarCloner configuration options with the existing non-default
     * options.
     *
     * Configuration options are:
     *  - casters: a map of VarDumper Caster objects to use instead of the default casters.
     *  - additional_casters: a map of VarDumper Caster objects to use in addition to the default
     *    casters.
     *  - max_items: maximum number of items to clone beyond the minimum depth.
     *  - max_string: maximum string size
     *  - min_depth: minimum tree depth to clone before counting items against the max_items limit.
     *
     */
    public function mergeClonerOptions(array $options): void
    {
        $this->clonerOptions = $options + $this->getClonerOptions();
        $this->cloner = null;
    }

    /**
     * Resets the array of non-default VarCloner configuration options without retaining any of the
     * existing non-default options.
     *
     * Configuration options are:
     *  - casters: a map of VarDumper Caster objects to use instead of the default casters.
     *  - additional_casters: a map of VarDumper Caster objects to use in addition to the default
     *    casters.
     *  - max_items: maximum number of items to clone beyond the minimum depth.
     *  - max_string: maximum string size
     *  - min_depth: minimum tree depth to clone before counting items against the max_items limit.
     *
     */
    public function resetClonerOptions(?array $options = null): void
    {
        $this->clonerOptions = ($options ?: []) + self::$defaultClonerOptions;
        $this->cloner = null;
    }

    /**
     * Gets the array of non-default HtmlDumper configuration options.
     *
     */
    public function getDumperOptions(): array
    {
        if ($this->dumperOptions === null) {
            $this->dumperOptions = self::$defaultDumperOptions;
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
        $this->dumperOptions = ($options ?: []) + self::$defaultDumperOptions;
        $this->dumper = null;
    }

    /**
     * Captures the data from a variable and serializes it for later rendering.
     *
     * @param mixed $data The variable to capture.
     *
     * @return string Serialized variable data.
     */
    public function captureVar(mixed $data): string
    {
        return serialize($this->getCloner()->cloneVar($data));
    }

    /**
     * Gets the display options for the HTML dumper.
     *
     */
    protected function getDisplayOptions(): array
    {
        $displayOptions = [];
        $dumperOptions = $this->getDumperOptions();
        // Only used by Symfony 3.2 and newer:
        if (isset($dumperOptions['expanded_depth'])) {
            $displayOptions['maxDepth'] = $dumperOptions['expanded_depth'];
        }
        // Only used by Symfony 3.2 and newer:
        if (isset($dumperOptions['max_string'])) {
            $displayOptions['maxStringLength'] = $dumperOptions['max_string'];
        }
        // Only used by Symfony 3.2 and newer:
        if (isset($dumperOptions['file_link_format'])) {
            $displayOptions['fileLinkFormat'] = $dumperOptions['file_link_format'];
        }
        return $displayOptions;
    }

    /**
     * Renders previously-captured data from captureVar to HTML and returns it as a string.
     *
     * @param string $capturedData Captured data from captureVar.
     * @param array  $seekPath     Pass an array of keys to traverse if you only want to render a subset
     *                             of the data.
     *
     * @return string HTML rendering of the variable.
     */
    public function renderCapturedVar(string $capturedData, array $seekPath = []): string
    {
        /** @var Data $data */
        $data = unserialize($capturedData);

        foreach ($seekPath as $key) {
            $data = $data->seek($key);
        }

        return $this->dump($data);
    }

    /**
     * Captures and renders the data from a variable to HTML and returns it as a string.
     *
     *
     */
    public function renderVar(mixed $data): string
    {
        return $this->dump($this->getCloner()->cloneVar($data));
    }

    /**
     * Returns assets required for rendering variables.
     *
     */
    public function getAssets(): array
    {
        $dumper = $this->getDumper();
        $dumper->resetDumpHeader(); // this will cause the default dump header to regenerate
        return [
            'inline_head' => [
                'html_var_dumper' => $dumper->getDumpHeaderByDebugBar(),
            ],
        ];
    }

    /**
     * Helper function to dump a Data object to HTML.
     *
     */
    protected function dump(Data $data): string
    {
        $dumper = $this->getDumper();
        $output = fopen('php://memory', 'r+b');
        $dumper->setOutput($output);
        $dumper->setDumpHeader(''); // we don't actually want a dump header
        // NOTE:  Symfony 3.2 added the third $extraDisplayOptions parameter.  Older versions will
        // safely ignore it.
        $dumper->dump($data, null, $this->getDisplayOptions());
        $result = stream_get_contents($output, -1, 0);
        fclose($output);
        return $result;
    }
}
