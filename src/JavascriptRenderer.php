<?php

declare(strict_types=1);

/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Renders the debug bar using the client side javascript implementation
 *
 * Generates all the needed initialization code of controls
 */
class JavascriptRenderer
{
    public const INITIALIZE_CONSTRUCTOR = 2;

    public const INITIALIZE_CONTROLS = 4;

    public const REPLACEABLE_TAG = "{--DEBUGBAR_OB_START_REPLACE_ME--}";

    public const RELATIVE_PATH = 'path';

    public const RELATIVE_URL = 'url';

    protected DebugBar $debugBar;

    protected ?string $baseUrl;

    protected string $basePath;

    /** @var array<string, string>  */
    protected array $cssVendors = [

    ];

    /** @var array<string, string>  */
    protected array $jsVendors = [
        'highlightjs' => 'vendor/highlightjs/highlight.pack.js',
        'sql-formatter' => 'vendor/sql-formatter/sql-formatter.min.js',
    ];

    protected bool|array $includeVendors = true;

    /** @var string[]  */
    protected array $cssFiles = ['debugbar.css', 'icons.css', 'widgets.css', 'openhandler.css', 'highlight.css'];

    /** @var string[]  */
    protected array $jsFiles = ['debugbar.js', 'widgets.js', 'openhandler.js'];

    protected bool $useDistFiles = true;

    /** @var string[]  */
    protected array $distCssFiles = ['dist/debugbar.min.css'];

    /** @var string[]  */
    protected array $distJsFiles = ['dist/debugbar.min.js'];

    /**
     * These files are included in the dist files. When using source, they are added by collectors if needed.
     *
     * @var array<int, string>
     */
    protected array $distIncludedAssets = [
        'vendor/highlightjs/highlight.pack.js',
        'vendor/sql-formatter/sql-formatter.min.js',
        'debugbar.js',
        'icons.css',
        'debugbar.css',
        'widgets.js',
        'widgets.css',
        'openhandler.js',
        'openhandler.css',
        'widgets/mails/widget.css',
        'widgets/mails/widget.js',
        'widgets/sqlqueries/widget.css',
        'widgets/sqlqueries/widget.js',
        'widgets/templates/widget.css',
        'widgets/templates/widget.js',
        'widgets/http/widget.js',
        'highlight.css',
    ];

    /** @var list<array{
     *   base_path?: string|null,
     *   base_url?: string|null,
     *   css?: string|array<int|string, string>,
     *   js?: string|array<int|string, string>,
     *   inline_css?: array<int|string, string>,
     *   inline_js?: array<int|string, string>,
     *   inline_head?: array<int|string, string>
     * }>
     */
    protected array $additionalAssets = [];

    protected string $javascriptClass = 'PhpDebugBar.DebugBar';

    protected string $variableName = 'phpdebugbar';

    protected ?string $theme = null;

    protected ?bool $hideEmptyTabs = null;

    protected int $initialization;

    /**
     * @var array<string, null|array{
     *    icon?: string,
     *    tooltip?: string,
     *    tab?: string,
     *    widget?: class-string,
     *    title?: string,
     *    map?: string,
     *    default?: string,
     *    indicator?: string,
     *    position?: 'left'|'right',
     *    order?: int
     *  }>
     */
    protected array $controls = [];

    protected array $ignoredCollectors = [];

    protected ?string $ajaxHandlerClass = 'PhpDebugBar.AjaxHandler';

    protected bool $ajaxHandlerBindToFetch = true;

    protected bool $ajaxHandlerBindToXHR = true;

    protected bool $ajaxHandlerAutoShow = true;

    protected bool $ajaxHandlerEnableTab = false;

    protected bool $deferDatasets = false;

    protected string $openHandlerClass = 'PhpDebugBar.OpenHandler';

    protected ?string $openHandlerUrl = null;

    protected ?string $assetHandlerUrl = null;

    protected ?string $cspNonce = null;

    public function __construct(DebugBar $debugBar, ?string $baseUrl = null, ?string $basePath = null)
    {
        $this->debugBar = $debugBar;

        if ($basePath === null) {
            $basePath = __DIR__ . '/../resources';
        }
        $this->basePath = $basePath;

        if ($baseUrl === null) {
            $pos = strpos($basePath, '/vendor/');
            if ($pos !== false) {
                $baseUrl = substr($basePath, $pos);
            } else {
                $baseUrl = '/vendor/php-debugbar/php-debugbar/resources';
            }
        }
        $this->baseUrl = $baseUrl;

        // bitwise operations cannot be done in class definition :(
        $this->initialization = self::INITIALIZE_CONSTRUCTOR | self::INITIALIZE_CONTROLS;
    }

    /**
     * @param array{
     *   base_path?: string,
     *   base_url?: string|null,
     *   include_vendors?: bool|array|string,
     *   javascript_class?: string,
     *   variable_name?: string,
     *   initialization?: int,
     *   use_dist_files?: bool,
     *   theme?: string|null,
     *   hide_empty_tabs?: bool,
     *   controls?: array<string, array{
     *     icon?: string,
     *     tooltip?: string,
     *     tab?: string,
     *     widget?: class-string,
     *     title?: string,
     *     map?: string,
     *     default?: string,
     *     indicator?: string,
     *     position?: string,
     *     order?: int
     *   }>,
     *   disable_controls?: string[],
     *   ignore_collectors?: string|string[],
     *   ajax_handler_classname?: string,
     *   ajax_handler_auto_show?: bool,
     *   ajax_handler_enable_tab?: bool,
     *   defer_datasets?: bool,
     *   open_handler_classname?: string,
     *   open_handler_url?: string,
     *   csp_nonce?: string|null
     * } $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('base_path', $options)) {
            $this->setBasePath($options['base_path']);
        }
        if (array_key_exists('base_url', $options)) {
            $this->setBaseUrl($options['base_url']);
        }
        if (array_key_exists('include_vendors', $options)) {
            $this->setIncludeVendors($options['include_vendors']);
        }
        if (array_key_exists('javascript_class', $options)) {
            $this->setJavascriptClass($options['javascript_class']);
        }
        if (array_key_exists('variable_name', $options)) {
            $this->setVariableName($options['variable_name']);
        }
        if (array_key_exists('initialization', $options)) {
            $this->setInitialization($options['initialization']);
        }
        if (array_key_exists('use_dist_files', $options)) {
            $this->setUseDistFiles($options['use_dist_files']);
        }
        if (array_key_exists('theme', $options)) {
            $this->setTheme($options['theme']);
        }
        if (array_key_exists('hide_empty_tabs', $options)) {
            $this->setHideEmptyTabs($options['hide_empty_tabs']);
        }
        if (array_key_exists('controls', $options)) {
            foreach ($options['controls'] as $name => $control) {
                $this->addControl($name, $control);
            }
        }
        if (array_key_exists('disable_controls', $options)) {
            foreach ((array) $options['disable_controls'] as $name) {
                $this->disableControl($name);
            }
        }
        if (array_key_exists('ignore_collectors', $options)) {
            foreach ((array) $options['ignore_collectors'] as $name) {
                $this->ignoreCollector($name);
            }
        }
        if (array_key_exists('ajax_handler_classname', $options)) {
            $this->setAjaxHandlerClass($options['ajax_handler_classname']);
        }
        if (array_key_exists('ajax_handler_auto_show', $options)) {
            $this->setAjaxHandlerAutoShow($options['ajax_handler_auto_show']);
        }
        if (array_key_exists('ajax_handler_enable_tab', $options)) {
            $this->setAjaxHandlerEnableTab($options['ajax_handler_enable_tab']);
        }
        if (array_key_exists('defer_datasets', $options)) {
            $this->setDeferDatasets($options['defer_datasets']);
        }
        if (array_key_exists('open_handler_classname', $options)) {
            $this->setOpenHandlerClass($options['open_handler_classname']);
        }
        if (array_key_exists('open_handler_url', $options)) {
            $this->setOpenHandlerUrl($options['open_handler_url']);
        }
        if (array_key_exists('csp_nonce', $options)) {
            $this->setCspNonce($options['csp_nonce']);
        }

        return $this;
    }

    /**
     * Sets the path which assets are relative to
     *
     */
    public function setBasePath(string $path): static
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Returns the path which assets are relative to
     *
     */
    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    /**
     * Sets the base URL from which assets will be served
     *
     */
    public function setBaseUrl(?string $url): static
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Returns the base URL from which assets will be served
     *
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Whether to include vendor assets
     *
     * You can only include js or css vendors using
     * setIncludeVendors('css') or setIncludeVendors('js')
     *
     * @param boolean|string|array $enabled
     */
    public function setIncludeVendors(bool|string|array $enabled = true): static
    {
        if (is_string($enabled)) {
            $enabled = [$enabled];
        }
        $this->includeVendors = $enabled;

        return $this;
    }

    /**
     * Checks if vendors assets are included
     *
     * @return boolean
     */
    public function areVendorsIncluded(): bool
    {
        return $this->includeVendors !== false;
    }

    /**
     * Disable a specific vendor's assets.
     *
     * @param string $name "highlightjs"
     *
     */
    public function disableVendor(string $name): static
    {
        if (array_key_exists($name, $this->cssVendors)) {
            unset($this->cssVendors[$name]);
        }
        if (array_key_exists($name, $this->jsVendors)) {
            unset($this->jsVendors[$name]);
        }
        return $this;
    }

    /**
     * Enables or disables using dist files instead of source files
     *
     */
    public function setUseDistFiles(bool $useDistFiles = true): static
    {
        $this->useDistFiles = $useDistFiles;
        return $this;
    }

    /**
     * Returns the usage of dist files.
     *
     */
    public function getUseDistFiles(): bool
    {
        return $this->useDistFiles;
    }

    /**
     * Sets the javascript class name
     *
     */
    public function setJavascriptClass(string $className): static
    {
        $this->javascriptClass = $className;
        return $this;
    }

    /**
     * Returns the javascript class name
     *
     */
    public function getJavascriptClass(): string
    {
        return $this->javascriptClass;
    }

    /**
     * Sets the variable name of the class instance
     *
     */
    public function setVariableName(string $name): static
    {
        $this->variableName = $name;
        return $this;
    }

    /**
     * Returns the variable name of the class instance
     *
     */
    public function getVariableName(): string
    {
        return $this->variableName;
    }

    /**
     * Sets what should be initialized
     *
     *  - INITIALIZE_CONSTRUCTOR: only initializes the instance
     *  - INITIALIZE_CONTROLS: initializes the controls and data mapping
     *  - INITIALIZE_CONSTRUCTOR | INITIALIZE_CONTROLS: initialize everything (default)
     *
     * @param integer $init
     */
    public function setInitialization(int $init): static
    {
        $this->initialization = $init;
        return $this;
    }

    /**
     * Returns what should be initialized
     *
     * @return integer
     */
    public function getInitialization(): int
    {
        return $this->initialization;
    }

    public function setTheme(?string $theme = 'auto'): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setHideEmptyTabs(bool $hide = true): static
    {
        $this->hideEmptyTabs = $hide;
        return $this;
    }

    public function areEmptyTabsHidden(): ?bool
    {
        return $this->hideEmptyTabs;
    }

    /**
     * Adds a control to initialize
     *
     * Possible options:
     *  - icon: icon name
     *  - tooltip: string
     *  - widget: widget class name
     *  - title: tab title
     *  - map: a property name from the data to map the control to
     *  - default: a js string, default value of the data map
     *
     * "icon" or "widget" are at least needed
     *
     * @param array{
     *   icon?: string,
     *   tooltip?: string,
     *   widget?: class-string,
     *   tab?: string,
     *   title?: string,
     *   map?: string,
     *   default?: string,
     *   indicator?: string,
     *   position?: 'left'|'right',
     *   order?: int
     * } $options
     *
     * @phpstan-assert (array{icon: string}|array{widget: class-string}) $options
     */
    public function addControl(string $name, array $options): static
    {
        if (count(array_intersect(array_keys($options), ['icon', 'widget', 'tab', 'indicator'])) === 0) {
            throw new DebugBarException("Not enough options for control '$name'");
        }
        $this->controls[$name] = $options;
        return $this;
    }

    /**
     * Disables a control
     *
     */
    public function disableControl(string $name): static
    {
        $this->controls[$name] = null;
        return $this;
    }

    /**
     * Returns the list of controls
     *
     * This does not include controls provided by collectors
     *
     */
    public function getControls(): array
    {
        return $this->controls;
    }

    /**
     * Ignores widgets provided by a collector
     *
     */
    public function ignoreCollector(string $name): static
    {
        $this->ignoredCollectors[] = $name;
        return $this;
    }

    /**
     * Returns the list of ignored collectors
     *
     */
    public function getIgnoredCollectors(): array
    {
        return $this->ignoredCollectors;
    }

    /**
     * Sets the class name of the ajax handler
     *
     * Set to null to disable
     *
     */
    public function setAjaxHandlerClass(?string $className): static
    {
        $this->ajaxHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the ajax handler
     *
     */
    public function getAjaxHandlerClass(): ?string
    {
        return $this->ajaxHandlerClass;
    }

    /**
     * Sets whether to call bindToFetch() on the ajax handler
     *
     * @param boolean $bind
     */
    public function setBindAjaxHandlerToFetch(bool $bind = true): static
    {
        $this->ajaxHandlerBindToFetch = $bind;
        return $this;
    }

    /**
     * Checks whether bindToFetch() will be called on the ajax handler
     *
     * @return boolean
     */
    public function isAjaxHandlerBoundToFetch(): bool
    {
        return $this->ajaxHandlerBindToFetch;
    }

    /**
     * Sets whether to call bindToXHR() on the ajax handler
     *
     * @param boolean $bind
     */
    public function setBindAjaxHandlerToXHR(bool $bind = true): static
    {
        $this->ajaxHandlerBindToXHR = $bind;
        return $this;
    }

    /**
     * Checks whether bindToXHR() will be called on the ajax handler
     *
     * @return boolean
     */
    public function isAjaxHandlerBoundToXHR(): bool
    {
        return $this->ajaxHandlerBindToXHR;
    }

    /**
     * Sets whether new ajax debug data will be immediately shown.  Setting to false could be useful
     * if there are a lot of tracking events cluttering things.
     *
     * @param boolean $autoShow
     */
    public function setAjaxHandlerAutoShow(bool $autoShow = true): static
    {
        $this->ajaxHandlerAutoShow = $autoShow;
        return $this;
    }

    /**
     * Checks whether the ajax handler will immediately show new ajax requests.
     *
     * @return boolean
     */
    public function isAjaxHandlerAutoShow(): bool
    {
        return $this->ajaxHandlerAutoShow;
    }

    /**
     * Sets whether new ajax debug data will be shown in a separate tab instead of dropdown.
     *
     * @param boolean $enabled
     */
    public function setAjaxHandlerEnableTab(bool $enabled = true): static
    {
        $this->ajaxHandlerEnableTab = $enabled;
        return $this;
    }

    /**
     * Check if the Ajax Handler History tab is enabled
     *
     * @return boolean
     */
    public function isAjaxHandlerTabEnabled(): bool
    {
        return $this->ajaxHandlerEnableTab;
    }

    /**
     * Sets whether datasets are directly loaded or deferred
     *
     * @param boolean $defer
     */
    public function setDeferDatasets(bool $defer = true): static
    {
        $this->deferDatasets = $defer;
        return $this;
    }

    /**
     * Check if the datasets are deffered
     *
     * @return boolean
     */
    public function areDatasetsDeferred(): bool
    {
        return $this->deferDatasets;
    }

    /**
     * Sets the class name of the js open handler
     *
     */
    public function setOpenHandlerClass(string $className): static
    {
        $this->openHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the js open handler
     *
     */
    public function getOpenHandlerClass(): string
    {
        return $this->openHandlerClass;
    }

    /**
     * Sets the url of the open handler
     *
     */
    public function setOpenHandlerUrl(?string $url): static
    {
        $this->openHandlerUrl = $url;
        return $this;
    }

    /**
     * Returns the url for the open handler
     *
     */
    public function getOpenHandlerUrl(): ?string
    {
        return $this->openHandlerUrl;
    }

    /**
     * Sets the url of the asset handler
     *
     */
    public function setAssetHandlerUrl(?string $url): static
    {
        $this->assetHandlerUrl = $url;
        return $this;
    }

    /**
     * Returns the url for the asset handler
     *
     */
    public function getAssetHandlerUrl(): ?string
    {
        return $this->assetHandlerUrl;
    }

    /**
     * Sets the CSP Nonce (or remove it by setting to null)
     *
     *
     * @return $this
     */
    public function setCspNonce(?string $nonce): static
    {
        $this->cspNonce = $nonce;
        return $this;
    }

    /**
     * Get the CSP Nonce
     *
     */
    public function getCspNonce(): ?string
    {
        return $this->cspNonce;
    }

    /**
     * Add assets stored in files to render in the head
     *
     * @param string[]|string $cssFiles An array of filenames
     * @param string[]|string $jsFiles  An array of filenames
     * @param ?string         $basePath Base path of those files
     * @param ?string         $baseUrl  Base url of those files
     *
     * @return $this
     */
    public function addAssets(array|string $cssFiles = [], array|string $jsFiles = [], ?string $basePath = null, ?string $baseUrl = null): static
    {
        $this->additionalAssets[] = [
            'base_path' => $basePath,
            'base_url' => $baseUrl,
            'css' => is_string($cssFiles) ? [$cssFiles] : $cssFiles,
            'js' => is_string($jsFiles) ? [$jsFiles] : $jsFiles,
        ];
        return $this;
    }

    /**
     * Add inline assets to render inline in the head.  Ideally, you should store static assets in
     * files that you add with the addAssets function.  However, adding inline assets is useful when
     * integrating with 3rd-party libraries that require static assets that are only available in an
     * inline format.
     *
     * The inline content arrays require special string array keys:  they are used to deduplicate
     * content.  This is particularly useful if multiple instances of the same asset end up being
     * added.  Inline assets from all collectors are merged together into the same array, so these
     * content IDs effectively deduplicate the inline assets.
     *
     * @param string[]|string $inlineCss  An array map of content ID to inline CSS content (not including <style> tag)
     * @param string[]|string $inlineJs   An array map of content ID to inline JS content (not including <script> tag)
     * @param string[]|string $inlineHead An array map of content ID to arbitrary inline HTML content (typically
     *                                    <style>/<script> tags); it must be embedded within the <head> element
     *
     * @return $this
     */
    public function addInlineAssets(array|string $inlineCss, array|string $inlineJs, array|string $inlineHead): static
    {
        $this->additionalAssets[] = [
            'inline_css' => is_string($inlineCss) ? [$inlineCss] : $inlineCss,
            'inline_js' => is_string($inlineJs) ? [$inlineJs] : $inlineJs,
            'inline_head' => is_string($inlineHead) ? [$inlineHead] : $inlineHead,
        ];
        return $this;
    }

    /**
     * Returns the list of asset files
     *
     * @param string|null $relativeTo The type of path to which filenames must be relative (path, url or null)
     *
     * @return array{css: string[], js: string[], inline_css: string[], inline_js: string[], inline_head: string[]}
     */
    public function getAssets(?string $relativeTo = self::RELATIVE_PATH): array
    {
        $cssFiles = $this->cssFiles;
        $jsFiles = $this->jsFiles;

        $inlineCss = [];
        $inlineJs = [];
        $inlineHead = [];

        if ($this->includeVendors !== false) {
            if ($this->includeVendors === true || in_array('css', $this->includeVendors, true)) {
                $cssFiles = array_merge($this->cssVendors, $cssFiles);
            }
            if ($this->includeVendors === true || in_array('js', $this->includeVendors, true)) {
                $jsFiles = array_merge($this->jsVendors, $jsFiles);
            }
        }

        if ($this->useDistFiles) {
            $cssFiles = array_filter(array_merge($this->distCssFiles, $cssFiles), function ($file): bool {
                return !in_array($file, $this->distIncludedAssets, true);
            });
            $jsFiles = array_filter(array_merge($this->distJsFiles, $jsFiles), function ($file): bool {
                return !in_array($file, $this->distIncludedAssets, true);
            });
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUrisRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUrisRelativeTo($jsFiles, $root);
        }

        $additionalAssets = $this->additionalAssets;
        // finds assets provided by collectors
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof AssetProvider) && !in_array($collector->getName(), $this->ignoredCollectors, true)) {
                $additionalAssets[] = $collector->getAssets();
            }
            if (($collector instanceof DataCollector) && !in_array($collector->getName(), $this->ignoredCollectors, true)) {
                $formatter = $collector->getDataFormatter();
                if ($formatter instanceof AssetProvider) {
                    $additionalAssets[] = $formatter->getAssets();
                }
            }
        }

        foreach ($additionalAssets as $assets) {
            $basePath = $assets['base_path'] ?? '';
            $baseUrl = $assets['base_url'] ?? '';
            $root = $this->getRelativeRoot(
                $relativeTo,
                $this->makeUriRelativeTo($basePath, $this->basePath),
                $this->makeUriRelativeTo($baseUrl, $this->baseUrl),
            );
            if (isset($assets['css']) && !($this->useDistFiles && $basePath === '' && in_array($assets['css'], $this->distIncludedAssets, true))) {
                $cssFiles = array_merge($cssFiles, $this->makeUrisRelativeTo(is_string($assets['css']) ? [$assets['css']] : $assets['css'], $root));
            }
            if (isset($assets['js']) && !($this->useDistFiles && $basePath === '' && in_array($assets['js'], $this->distIncludedAssets, true))) {
                $jsFiles = array_merge($jsFiles, $this->makeUrisRelativeTo(is_string($assets['js']) ? [$assets['js']] : $assets['js'], $root));
            }

            if (isset($assets['inline_css'])) {
                $inlineCss = array_merge($inlineCss, (array) $assets['inline_css']);
            }
            if (isset($assets['inline_js'])) {
                $inlineJs = array_merge($inlineJs, (array) $assets['inline_js']);
            }
            if (isset($assets['inline_head'])) {
                $inlineHead = array_merge($inlineHead, (array) $assets['inline_head']);
            }
        }

        // Deduplicate files
        $cssFiles = array_unique($cssFiles);
        $jsFiles = array_unique($jsFiles);

        return [
            'css' => $cssFiles,
            'js' => $jsFiles,
            'inline_css' => $inlineCss,
            'inline_js' => $inlineJs,
            'inline_head' => $inlineHead,
        ];
    }

    /**
     * @return array{css: string[], js: string[]}
     */
    public function getDistIncludedAssets(?string $relativeTo = self::RELATIVE_PATH): array
    {
        $cssFiles = [];
        $jsFiles = [];
        foreach ($this->distIncludedAssets as $asset) {
            $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
            if ($ext === 'css') {
                $cssFiles[] = $asset;
            } elseif ($ext === 'js') {
                $jsFiles[] = $asset;
            }
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUrisRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUrisRelativeTo($jsFiles, $root);
        }

        return [
            'css' => $cssFiles,
            'js' => $jsFiles,
        ];
    }

    /**
     * Returns the correct base according to the type
     *
     *
     */
    protected function getRelativeRoot(?string $relativeTo, string $basePath, ?string $baseUrl): ?string
    {
        if ($relativeTo === self::RELATIVE_PATH) {
            return $basePath;
        }
        if ($baseUrl && $relativeTo === self::RELATIVE_URL) {
            return $baseUrl;
        }
        return null;
    }

    /**
     * @param string[] $uris
     *
     * @return string[]
     */
    protected function makeUrisRelativeTo(array $uris, ?string $root): array
    {
        if ($root === null) {
            return $uris;
        }

        $relativeUris = [];
        foreach ($uris as $u) {
            $relativeUris[] = $this->makeUriRelativeTo($u, $root);
        }

        return $relativeUris;
    }

    protected function makeUriRelativeTo(string $uri, ?string $root): string
    {
        if ($root === null) {
            return $uri;
        }

        if (str_starts_with($uri, '/') || preg_match('/^([a-zA-Z]+:\/\/|[a-zA-Z]:\/|[a-zA-Z]:\\\)/', $uri)) {
            return $uri;
        }

        return rtrim($root, '/') . "/$uri";
    }

    /**
     * Write all CSS assets to standard output or in a file
     *
     */
    public function dumpCssAssets(?string $targetFilename = null, bool $echo = true): string
    {
        $assets = $this->getAssets();
        return $this->dumpAssets($assets['css'], $assets['inline_css'], $targetFilename, $echo);
    }

    /**
     * Write all JS assets to standard output or in a file
     *
     */
    public function dumpJsAssets(?string $targetFilename = null, bool $echo = true): string
    {
        $assets = $this->getAssets();
        return $this->dumpAssets($assets['js'], $assets['inline_js'], $targetFilename, $echo);
    }

    /**
     * Write all inline HTML header assets to standard output or in a file (only returns assets not
     * already returned by dumpCssAssets or dumpJsAssets)
     *
     */
    public function dumpHeadAssets(?string $targetFilename = null, bool $echo = true): string
    {
        return $this->dumpAssets(null, $this->getAssets()['inline_head'], $targetFilename, $echo);
    }

    /**
     * Write assets to standard output or in a file
     *
     * @param string[]|null $files   Filenames containing assets
     * @param string[]|null $content Inline content to dump
     */
    public function dumpAssets(?array $files = null, ?array $content = null, ?string $targetFilename = null, bool $echo = true): string
    {
        $dumpedContent = '';
        if ($files) {
            foreach ($files as $file) {
                $dumpedContent .= file_get_contents($file) . "\n";
            }
        }
        if ($content) {
            foreach ($content as $item) {
                $dumpedContent .= $item . "\n";
            }
        }

        if ($targetFilename !== null) {
            file_put_contents($targetFilename, $dumpedContent);
        } elseif ($echo) {
            $this->debugBar->getHttpDriver()->output($dumpedContent);
        }

        return $dumpedContent;
    }

    /**
     * Renders the html to include needed assets
     *
     *
     */
    public function renderHead(): string
    {
        [
            'css' => $cssFiles,
            'js' => $jsFiles,
            'inline_css' => $inlineCss,
            'inline_js' => $inlineJs,
            'inline_head' => $inlineHead,
        ] = $this->getAssets(self::RELATIVE_URL);

        if ($this->assetHandlerUrl !== null) {
            $url = $this->assetHandlerUrl;
            $assets = $this->getAssets(self::RELATIVE_PATH);
            $cssFiles = [$url . (str_contains($url, '?') ? '&' : '?') . 'type=css&mtime=' . $this->getFilesModifiedTime($assets['css'])];
            $jsFiles = [$url . (str_contains($url, '?') ? '&' : '?') . 'type=js&hash=' . $this->getFilesModifiedTime($assets['js'])];
        }
        $html = '';

        $nonce = $this->getNonceAttribute();

        foreach ($cssFiles as $file) {
            $html .= sprintf('<link rel="stylesheet" type="text/css"%s href="%s">' . "\n", $nonce, $file);
        }

        foreach ($inlineCss as $content) {
            $html .= sprintf('<style%s>%s</style>' . "\n", $nonce, $content);
        }

        foreach ($jsFiles as $file) {
            $html .= sprintf('<script type="text/javascript"%s src="%s"></script>' . "\n", $nonce, $file);
        }

        foreach ($inlineJs as $content) {
            $html .= sprintf('<script type="text/javascript"%s>%s</script>' . "\n", $nonce, $content);
        }

        foreach ($inlineHead as $content) {
            if ($nonce !== '') {
                $content =  preg_replace(
                    '/<(script|style)(?![^>]*nonce=)/i',
                    '<$1' . $nonce,
                    $content
                );
            }

            $html .= $content . "\n";
        }

        return $html;
    }

    /**
     * @param string[] $files
     */
    protected function getFilesModifiedTime(array $files): int
    {
        $modifiedTime = 0;
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            $modifiedTime = $fileTime !== false ? max($modifiedTime, $fileTime) : $modifiedTime;
        }
        return $modifiedTime;
    }

    public function injectInHtmlResponse(string $content, bool $withHead = true): string
    {
        $widget = "<!-- Laravel Debugbar Widget -->\n" . ($withHead ? $this->renderHead() : '') . $this->render();

        // Try to put the widget at the end, directly before the </body>
        $pos = strripos($content, '</body>');
        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $widget . substr($content, $pos);
        } else {
            $content = $content . $widget;
        }

        return $content;
    }

    /**
     * Register shutdown to display the debug bar
     *
     * @param boolean $here       Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     *
     * @return string Return "{--DEBUGBAR_OB_START_REPLACE_ME--}" or return an empty string if $here == false
     */
    public function renderOnShutdown(bool $here = true, bool $initialize = true, bool $renderStackedData = true, bool $head = false): string
    {
        register_shutdown_function([$this, "replaceTagInBuffer"], $here, $initialize, $renderStackedData, $head);

        if (ob_get_level() === 0) {
            ob_start();
        }

        return $here ? self::REPLACEABLE_TAG : "";
    }

    /**
     * Same as renderOnShutdown() with $head = true
     *
     * @param boolean $here
     * @param boolean $initialize
     * @param boolean $renderStackedData
     *
     */
    public function renderOnShutdownWithHead(bool $here = true, bool $initialize = true, bool$renderStackedData = true): string
    {
        return $this->renderOnShutdown($here, $initialize, $renderStackedData, true);
    }

    /**
     * Is callback function for register_shutdown_function(...)
     *
     * @param boolean $here       Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     */
    public function replaceTagInBuffer(bool $here = true, bool $initialize = true, bool $renderStackedData = true, bool $head = false): void
    {
        $render = ($head ? $this->renderHead() : "")
            . $this->render($initialize, $renderStackedData);

        $current = ($here && ob_get_level() > 0) ? ob_get_clean() : self::REPLACEABLE_TAG;

        echo str_replace(self::REPLACEABLE_TAG, $render, $current ?: '', $count);

        if ($count === 0) {
            echo $render;
        }
    }

    /**
     * Returns the code needed to display the debug bar
     *
     * AJAX request should not render the initialization code.
     *
     * @param boolean $initialize        Whether or not to render the debug bar initialization code
     * @param boolean $renderStackedData Whether or not to render the stacked data
     *
     */
    public function render(bool $initialize = true, bool $renderStackedData = true): string
    {
        $js = $initialize ? $this->getJsInitializationCode() : '';

        if ($renderStackedData && $this->debugBar->hasStackedData()) {
            foreach ($this->debugBar->getStackedData() as $id => $data) {
                if ($this->areDatasetsDeferred()) {
                    $js .= $this->getLoadDatasetCode($id, '(stacked)', false);
                } else {
                    $js .= $this->getAddDatasetCode($id, $data, '(stacked)', false);

                }
            }
        }

        $suffix = !$initialize ? '(ajax)' : null;
        if ($this->areDatasetsDeferred()) {
            $this->debugBar->getData();
            $js .= $this->getLoadDatasetCode($this->debugBar->getCurrentRequestId(), $suffix);
        } else {
            $js .= $this->getAddDatasetCode($this->debugBar->getCurrentRequestId(), $this->debugBar->getData(), $suffix);
        }

        $nonce = $this->getNonceAttribute();
        $varName = $this->getVariableName();

        return "<script type=\"text/javascript\"{$nonce}>
(function () {
    const renderDebugbar = function () {
$js
    window.PhpDebugBar.instance = {$varName};
    window.{$varName} = {$varName};
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderDebugbar, { once: true });
    } else {
        renderDebugbar();
    }
})();
</script>";
    }

    /**
     * Returns the js code needed to initialize the debug bar
     *
     */
    protected function getJsInitializationCode(): string
    {
        $js = '';

        if (($this->initialization & self::INITIALIZE_CONSTRUCTOR) === self::INITIALIZE_CONSTRUCTOR) {
            $initializeOptions = $this->getInitializeOptions();
            $js .= sprintf("const %s = new %s(%s);\n", $this->variableName, $this->javascriptClass, $initializeOptions ? json_encode((object) $initializeOptions) : '');
        }

        if (($this->initialization & self::INITIALIZE_CONTROLS) === self::INITIALIZE_CONTROLS) {
            $js .= $this->getJsControlsDefinitionCode($this->variableName);
        }

        if ($this->ajaxHandlerClass) {
            $js .= sprintf(
                "%s.ajaxHandler = new %s(%s, undefined, %s);\n",
                $this->variableName,
                $this->ajaxHandlerClass,
                $this->variableName,
                $this->ajaxHandlerAutoShow ? 'true' : 'false',
            );
            if ($this->ajaxHandlerBindToFetch) {
                $js .= sprintf("%s.ajaxHandler.bindToFetch();\n", $this->variableName);
            }
            if ($this->ajaxHandlerBindToXHR) {
                $js .= sprintf("%s.ajaxHandler.bindToXHR();\n", $this->variableName);
            }
        }

        if ($this->openHandlerUrl !== null) {
            $js .= sprintf(
                "%s.setOpenHandler(new %s(%s));\n",
                $this->variableName,
                $this->openHandlerClass,
                json_encode(["url" => $this->openHandlerUrl]),
            );
        }

        return $js;
    }

    protected function getInitializeOptions(): array
    {
        $options = [];

        if ($this->theme !== null) {
            $options['theme'] = $this->theme;
        }

        if ($this->hideEmptyTabs !== null) {
            $options['hideEmptyTabs'] = $this->hideEmptyTabs;
        }

        return $options;
    }

    /**
     * Returns the js code needed to initialized the controls and data mapping of the debug bar
     *
     * Controls can be defined by collectors themselves or using {@see addControl()}
     *
     *
     */
    protected function getJsControlsDefinitionCode(string $varname): string
    {
        $js = '';
        $dataMap = [];
        $excludedOptions = ['indicator', 'tab', 'map', 'default', 'widget', 'position'];

        // finds controls provided by collectors
        $widgets = [];
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof Renderable) && !in_array($collector->getName(), $this->ignoredCollectors, true)) {
                if ($w = $collector->getWidgets()) {
                    $widgets = array_merge($widgets, $w);
                }
            }
        }
        $controls = array_merge($widgets, $this->controls);

        // Allow widgets to be sorted by order if specified
        uasort($controls, function (mixed $controlA, mixed $controlB): int {
            return ($controlA['order'] ?? 0) <=> ($controlB['order'] ?? 0);
        });

        foreach (array_filter($controls) as $name => $options) {
            $opts = array_diff_key($options, array_flip($excludedOptions));

            if (isset($options['tab']) || isset($options['widget'])) {
                if (!isset($opts['title'])) {
                    $opts['title'] = ucfirst(str_replace('_', ' ', $name));
                }
                $jsonOpts = json_encode($opts, JSON_FORCE_OBJECT);
                $js .= sprintf(
                    "%s.addTab(\"%s\", new %s({%s%s}));\n",
                    $varname,
                    $name,
                    $options['tab'] ?? 'PhpDebugBar.DebugBar.Tab',
                    substr($jsonOpts === false ? '' : $jsonOpts, 1, -1),
                    isset($options['widget']) ? sprintf(', "widget": new %s()', $options['widget']) : '',
                );
            } elseif (isset($options['indicator']) || isset($options['icon'])) {
                $jsonOpts = json_encode($opts, JSON_FORCE_OBJECT);
                $js .= sprintf(
                    "%s.addIndicator(\"%s\", new %s(%s), \"%s\");\n",
                    $varname,
                    $name,
                    $options['indicator'] ?? 'PhpDebugBar.DebugBar.Indicator',
                    $jsonOpts === false ? '' : $jsonOpts,
                    $options['position'] ?? 'right',
                );
            }

            if (isset($options['map']) && isset($options['default'])) {
                $dataMap[$name] = [$options['map'], $options['default']];
            }
        }

        // creates the data mapping object
        $mapJson = [];
        foreach ($dataMap as $name => $values) {
            $mapJson[] = sprintf('"%s": ["%s", %s]', $name, $values[0], $values[1]);
        }
        $js .= sprintf("%s.setDataMap({\n%s\n});\n", $varname, implode(",\n", $mapJson));

        // activate state restoration
        $js .= sprintf("%s.restoreState();\n", $varname);

        if ($this->ajaxHandlerEnableTab) {
            $js .= sprintf("%s.enableAjaxHandlerTab();\n", $varname);
        }
        return $js;
    }

    /**
     * Returns the js code needed to add a dataset
     *
     *
     */
    protected function getAddDatasetCode(string $requestId, array $data, ?string $suffix = null, bool $show = true): string
    {
        $js = sprintf(
            "%s.addDataSet(%s, %s, %s, %s);\n",
            $this->variableName,
            json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_IGNORE),
            json_encode($requestId),
            json_encode($suffix ?: ''),
            json_encode($show),
        );
        return $js;
    }

    /**
     * Returns the js code needed to load a dataset with the OpenHandler
     *
     *
     */
    protected function getLoadDatasetCode(string $requestId, ?string $suffix = null, bool $show = true): string
    {
        $js = sprintf(
            "%s.loadDataSet(%s, %s, null, %s);\n",
            $this->variableName,
            json_encode($requestId),
            json_encode($suffix ?: ''),
            json_encode($show),
        );
        return $js;
    }

    /**
     * If a nonce it set, create the correct attribute
     *
     */
    protected function getNonceAttribute(): ?string
    {
        if ($nonce = $this->getCspNonce()) {
            return ' nonce="' . $nonce . '"';
        }

        return '';
    }
}
