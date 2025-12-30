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

    protected ?string $basePath;

    protected array $cssVendors = [

    ];

    protected array $jsVendors = [
        'highlightjs' => 'vendor/highlightjs/highlight.pack.js',
    ];

    protected bool|array $includeVendors = true;

    protected array $cssFiles = ['debugbar.css', 'icons.css', 'widgets.css', 'openhandler.css', 'highlight.css'];

    protected array $jsFiles = ['debugbar.js', 'widgets.js', 'openhandler.js'];

    protected bool $useDistFiles = true;

    protected array $distCssFiles = ['../dist/debugbar.min.css'];

    protected array $distJsFiles = ['../dist/debugbar.min.js'];

    /*
     * These files are included in the dist files. When using source, they are added by collectors if needed.
     */
    protected array $distIncludedAssets = [
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
        'highlight.css',
        'vendor/highlightjs/highlight.pack.js',
    ];

    protected array $additionalAssets = [];

    protected string $javascriptClass = 'PhpDebugBar.DebugBar';

    protected string $variableName = 'phpdebugbar';

    protected ?string $theme = null;

    protected ?bool $hideEmptyTabs = null;

    protected int $initialization;

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

    protected ?string $cspNonce = null;

    public function __construct(DebugBar $debugBar, ?string $baseUrl = null, ?string $basePath = null)
    {
        $this->debugBar = $debugBar;

        if ($basePath === null) {
            $basePath = __DIR__ . '/../../resources';
        }
        $this->basePath = $basePath;

        if ($baseUrl === null) {
            if ($basePath && str_contains($basePath, '/vendor/')) {
                $baseUrl = strstr($basePath, '/vendor/');
            } else {
                $baseUrl = '/vendor/php-debugbar/php-debugbar/resources';
            }
        }
        $this->baseUrl = $baseUrl;

        // bitwise operations cannot be done in class definition :(
        $this->initialization = self::INITIALIZE_CONSTRUCTOR | self::INITIALIZE_CONTROLS;
    }

    /**
     * Sets options from an array
     *
     * Options:
     *  - base_path
     *  - base_url
     *  - include_vendors
     *  - javascript_class
     *  - variable_name
     *  - initialization
     *  - use_dist_files
     *  - controls
     *  - disable_controls
     *  - ignore_collectors
     *  - ajax_handler_classname
     *  - ajax_handler_auto_show
     *  - open_handler_classname
     *  - open_handler_url
     *
     * @param array $options [description]
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
     * @param string $path
     */
    public function setBasePath($path): static
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

    /**
     * Sets the default theme
     *
     *
     * @return $this
     */
    public function setTheme(?string $theme = 'auto'): static
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Sets whether to hide empty tabs or not
     *
     * @param boolean $hide
     *
     * @return $this
     */
    public function setHideEmptyTabs(bool $hide = true): static
    {
        $this->hideEmptyTabs = $hide;
        return $this;
    }

    /**
     * Checks if empty tabs are hidden or not
     *
     * @return boolean
     */
    public function areEmptyTabsHidden(): bool
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
     * @param array|string $cssFiles An array of filenames
     * @param array|string $jsFiles  An array of filenames
     * @param ?string      $basePath Base path of those files
     * @param ?string      $baseUrl  Base url of those files
     *
     * @return $this
     */
    public function addAssets(array|string $cssFiles, array|string $jsFiles, ?string $basePath = null, ?string $baseUrl = null): static
    {
        $this->additionalAssets[] = [
            'base_path' => $basePath,
            'base_url' => $baseUrl,
            'css' => (array) $cssFiles,
            'js' => (array) $jsFiles,
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
     * @param array|string $inlineCss  An array map of content ID to inline CSS content (not including <style> tag)
     * @param array|string $inlineJs   An array map of content ID to inline JS content (not including <script> tag)
     * @param array|string $inlineHead An array map of content ID to arbitrary inline HTML content (typically
     *                                 <style>/<script> tags); it must be embedded within the <head> element
     *
     * @return $this
     */
    public function addInlineAssets(array|string $inlineCss, array|string $inlineJs, array|string $inlineHead)
    {
        $this->additionalAssets[] = [
            'inline_css' => (array) $inlineCss,
            'inline_js' => (array) $inlineJs,
            'inline_head' => (array) $inlineHead,
        ];
        return $this;
    }

    /**
     * Returns the list of asset files
     *
     * @param string|null $type       'css', 'js', 'inline_css', 'inline_js', 'inline_head', or null for all
     * @param string|null $relativeTo The type of path to which filenames must be relative (path, url or null)
     *
     * @return ($type is null ? array{css: string[], js: string[], inline_css: string[], inline_js: string[], inline_head: string[]} : string[])
     */
    public function getAssets(?string $type = null, ?string $relativeTo = self::RELATIVE_PATH): array
    {
        $cssFiles = $this->cssFiles;
        $jsFiles = $this->jsFiles;

        $inlineCss = [];
        $inlineJs = [];
        $inlineHead = [];

        if ($this->includeVendors !== false) {
            if ($this->includeVendors === true || in_array('css', $this->includeVendors)) {
                $cssFiles = array_merge($this->cssVendors, $cssFiles);
            }
            if ($this->includeVendors === true || in_array('js', $this->includeVendors)) {
                $jsFiles = array_merge($this->jsVendors, $jsFiles);
            }
        }

        if ($this->useDistFiles) {
            $cssFiles = array_merge($this->distCssFiles, $cssFiles);
            $cssFiles = array_filter($cssFiles, function ($file) {
                return !in_array($file, $this->distIncludedAssets);
            });
            $jsFiles = array_merge($this->distJsFiles, $jsFiles);
            $jsFiles = array_filter($jsFiles, function ($file) {
                return !in_array($file, $this->distIncludedAssets);
            });
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUriRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUriRelativeTo($jsFiles, $root);
        }

        $additionalAssets = $this->additionalAssets;
        // finds assets provided by collectors
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof AssetProvider) && !in_array($collector->getName(), $this->ignoredCollectors)) {
                $additionalAssets[] = $collector->getAssets();
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
            if (isset($assets['css']) && !($this->useDistFiles && $basePath === '' && in_array($assets['css'], $this->distIncludedAssets))) {
                $cssFiles = array_merge($cssFiles, $this->makeUriRelativeTo((array) $assets['css'], $root));
            }
            if (isset($assets['js']) && !($this->useDistFiles && $basePath === '' && in_array($assets['js'], $this->distIncludedAssets))) {
                $jsFiles = array_merge($jsFiles, $this->makeUriRelativeTo((array) $assets['js'], $root));
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

        $files = [
            'css' => $cssFiles,
            'js' => $jsFiles,
            'inline_css' => $inlineCss,
            'inline_js' => $inlineJs,
            'inline_head' => $inlineHead,
        ];

        return $type ? $files[$type] : $files;
    }

    /**
     * @return ($type is null ? array{css: string[], js: string[]} : string[])
     */
    public function getDistIncludedAssets(?string $type = null, ?string $relativeTo = self::RELATIVE_PATH): array
    {
        $cssFiles = [];
        $jsFiles = [];
        foreach ($this->distIncludedAssets as $asset) {
            $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
            if ($ext == 'css') {
                $cssFiles[] = $asset;
            } elseif ($ext == 'js') {
                $jsFiles[] = $asset;
            }
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUriRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUriRelativeTo($jsFiles, $root);
        }

        $files = [
            'css' => $cssFiles,
            'js' => $jsFiles,
        ];

        return $type ? $files[$type] : $files;
    }

    /**
     * Returns the correct base according to the type
     *
     *
     */
    protected function getRelativeRoot(string $relativeTo, string $basePath, string $baseUrl): ?string
    {
        if ($relativeTo === self::RELATIVE_PATH) {
            return $basePath;
        }
        if ($relativeTo === self::RELATIVE_URL) {
            return $baseUrl;
        }
        return null;
    }

    /**
     * Makes a URI relative to another
     *
     */
    protected function makeUriRelativeTo(string|array|null $uri, string $root): string|array
    {
        if (!$root) {
            return $uri;
        }

        if (is_array($uri)) {
            $uris = [];
            foreach ($uri as $u) {
                $uris[] = $this->makeUriRelativeTo($u, $root);
            }
            return $uris;
        }

        $uri ??= '';

        if (substr($uri, 0, 1) === '/' || preg_match('/^([a-zA-Z]+:\/\/|[a-zA-Z]:\/|[a-zA-Z]:\\\)/', $uri)) {
            return $uri;
        }
        return rtrim($root, '/') . "/$uri";
    }

    /**
     * Write all CSS assets to standard output or in a file
     *
     */
    public function dumpCssAssets(?string $targetFilename = null): void
    {
        $this->dumpAssets($this->getAssets('css'), $this->getAssets('inline_css'), $targetFilename);
    }

    /**
     * Write all JS assets to standard output or in a file
     *
     */
    public function dumpJsAssets(?string $targetFilename = null): void
    {
        $this->dumpAssets($this->getAssets('js'), $this->getAssets('inline_js'), $targetFilename);
    }

    /**
     * Write all inline HTML header assets to standard output or in a file (only returns assets not
     * already returned by dumpCssAssets or dumpJsAssets)
     *
     */
    public function dumpHeadAssets(?string $targetFilename = null): void
    {
        $this->dumpAssets(null, $this->getAssets('inline_head'), $targetFilename);
    }

    /**
     * Write assets to standard output or in a file
     *
     * @param array|null $files   Filenames containing assets
     * @param array|null $content Inline content to dump
     */
    public function dumpAssets(?array $files = null, ?array $content = null, ?string $targetFilename = null): void
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
        } else {
            echo $dumpedContent;
        }
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
        ] = $this->getAssets(null, self::RELATIVE_URL);

        $html = '';

        $nonce = $this->getNonceAttribute();

        foreach ($cssFiles as $file) {
            $html .= sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n", $file);
        }

        foreach ($inlineCss as $content) {
            $html .= sprintf('<style type="text/css"%s>%s</style>' . "\n", $nonce, $content);
        }

        foreach ($jsFiles as $file) {
            $html .= sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $file);
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

        echo str_replace(self::REPLACEABLE_TAG, $render, $current, $count);

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
        $js = '';

        if ($initialize) {
            $js = $this->getJsInitializationCode();
        }

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

        if ($nonce != '') {
            $js =  preg_replace(
                '/<(script)(?![^>]*nonce=)/i',
                '<$1' . $nonce,
                $js
            );
        }

        return "<script type=\"text/javascript\"{$nonce}>\n$js\n</script>\n";
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
            $js .= sprintf("var %s = new %s(%s);\n", $this->variableName, $this->javascriptClass, $initializeOptions ? json_encode((object) $initializeOptions) : '');
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
            if (($collector instanceof Renderable) && !in_array($collector->getName(), $this->ignoredCollectors)) {
                if ($w = $collector->getWidgets()) {
                    $widgets = array_merge($widgets, $w);
                }
            }
        }
        $controls = array_merge($widgets, $this->controls);

        // Allow widgets to be sorted by order if specified
        uasort($controls, function (array $controlA, array $controlB) {
            return ($controlA['order'] ?? 0) <=> ($controlB['order'] ?? 0);
        });

        foreach (array_filter($controls) as $name => $options) {
            $opts = array_diff_key($options, array_flip($excludedOptions));

            if (isset($options['tab']) || isset($options['widget'])) {
                if (!isset($opts['title'])) {
                    $opts['title'] = ucfirst(str_replace('_', ' ', $name));
                }
                $js .= sprintf(
                    "%s.addTab(\"%s\", new %s({%s%s}));\n",
                    $varname,
                    $name,
                    $options['tab'] ?? 'PhpDebugBar.DebugBar.Tab',
                    substr(json_encode($opts, JSON_FORCE_OBJECT), 1, -1),
                    isset($options['widget']) ? sprintf(', "widget": new %s()', $options['widget']) : '',
                );
            } elseif (isset($options['indicator']) || isset($options['icon'])) {
                $js .= sprintf(
                    "%s.addIndicator(\"%s\", new %s(%s), \"%s\");\n",
                    $varname,
                    $name,
                    $options['indicator'] ?? 'PhpDebugBar.DebugBar.Indicator',
                    json_encode($opts, JSON_FORCE_OBJECT),
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
            "%s.loadDataSet(%s, %s, %s);\n",
            json_encode($this->variableName),
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
