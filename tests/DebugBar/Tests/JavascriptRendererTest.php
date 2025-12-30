<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\JavascriptRenderer;

class JavascriptRendererTest extends DebugBarTestCase
{
    protected JavascriptRenderer $r;

    public function setUp(): void
    {
        parent::setUp();
        $this->r = new JavascriptRenderer($this->debugbar);
        $this->r->setBasePath('/bpath');
        $this->r->setBaseUrl('/burl');
        $this->r->setUseDistFiles(false);
    }

    public function testOptions(): void
    {
        $this->r->setOptions([
            'base_path' => '/foo',
            'base_url' => '/foo',
            'include_vendors' => false,
            'javascript_class' => 'Foobar',
            'variable_name' => 'foovar',
            'initialization' => JavascriptRenderer::INITIALIZE_CONTROLS,
            'controls' => [
                'memory' => [
                    "icon" => "cogs",
                    "map" => "memory.peak_usage_str",
                    "default" => "'0B'",
                ],
            ],
            'disable_controls' => ['messages'],
            'ignore_collectors' => 'config',
            'ajax_handler_classname' => 'AjaxFoo',
            'ajax_handler_auto_show' => false,
            'open_handler_classname' => 'OpenFoo',
            'open_handler_url' => 'open.php',
        ]);

        $this->assertEquals('/foo', $this->r->getBasePath());
        $this->assertEquals('/foo', $this->r->getBaseUrl());
        $this->assertFalse($this->r->areVendorsIncluded());
        $this->assertEquals('Foobar', $this->r->getJavascriptClass());
        $this->assertEquals('foovar', $this->r->getVariableName());
        $this->assertEquals(JavascriptRenderer::INITIALIZE_CONTROLS, $this->r->getInitialization());
        $controls = $this->r->getControls();
        $this->assertCount(2, $controls);
        $this->assertArrayHasKey('memory', $controls);
        $this->assertArrayHasKey('messages', $controls);
        $this->assertNull($controls['messages']);
        $this->assertContains('config', $this->r->getIgnoredCollectors());
        $this->assertEquals('AjaxFoo', $this->r->getAjaxHandlerClass());
        $this->assertTrue($this->r->isAjaxHandlerBoundToFetch());
        $this->assertFalse($this->r->isAjaxHandlerAutoShow());
        $this->assertEquals('OpenFoo', $this->r->getOpenHandlerClass());
        $this->assertEquals('open.php', $this->r->getOpenHandlerUrl());
    }

    public function testAddAssets(): void
    {
        // Use a loop to test deduplication of assets
        for ($i = 0; $i < 2; ++$i) {
            $this->r->addAssets('foo.css', 'foo.js', '/bar', '/foobar');
            $this->r->addInlineAssets(['Css' => 'CssTest'], ['Js' => 'JsTest'], ['Head' => 'HeaderTest']);
        }

        // Make sure all the right assets are returned by getAssets
        $assets = $this->r->getAssets();
        $this->assertContains('/bar/foo.css', $assets['css']);
        $this->assertContains('/bar/foo.js', $assets['js']);
        $this->assertEquals(['Css' => 'CssTest'], $assets['inline_css']);
        $this->assertEquals(['Js' => 'JsTest'], $assets['inline_js']);
        $this->assertEquals(['Head' => 'HeaderTest'], $assets['inline_head']);

        // Make sure asset files are deduplicated
        $this->assertCount(count(array_unique($assets['css'])), $assets['css']);
        $this->assertCount(count(array_unique($assets['js'])), $assets['js']);

        $html = $this->r->renderHead();
        $this->assertStringContainsString('<script type="text/javascript" src="/foobar/foo.js"></script>', $html);
    }

    public function testGetAssets(): void
    {
        $assets = $this->r->getAssets();
        $this->assertContains('/bpath/debugbar.css', $assets['css']);
        $this->assertContains('/bpath/widgets.js', $assets['js']);
        $this->assertContains('/bpath/vendor/highlightjs/highlight.pack.js', $assets['js']);
    }

    public function testGetAssetsExludeVendors(): void
    {
        $this->r->setIncludeVendors(false);
        $js = $this->r->getAssets('js');
        $this->assertContains('/bpath/debugbar.js', $js);
        $this->assertNotContains('/bpath/vendor/highlightjs/highlight.pack.js', $js);
    }

    public function testGetDistAssets(): void
    {
        $this->r->setUseDistFiles(true);
        $assets = $this->r->getAssets();
        $this->assertContains('/bpath/../dist/debugbar.min.css', $assets['css']);
        $this->assertNotContains('/bpath/widgets.js', $assets['js']);
        $this->assertNotContains('/bpath/vendor/highlightjs/highlight.pack.js', $assets['js']);
    }

    public function testRenderHead(): void
    {
        $this->r->addInlineAssets(['Css' => 'CssTest'], ['Js' => 'JsTest'], ['Head' => 'HeaderTest']);

        $html = $this->r->renderHead();
        // Check for file links
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="/burl/debugbar.css">', $html);
        $this->assertStringContainsString('<script type="text/javascript" src="/burl/debugbar.js"></script>', $html);
        // Check for inline assets
        $this->assertStringContainsString('<style type="text/css">CssTest</style>', $html);
        $this->assertStringContainsString('<script type="text/javascript">JsTest</script>', $html);
        $this->assertStringContainsString('HeaderTest', $html);
    }

    public function testRenderHeadWithCsp(): void
    {
        $this->r->setCspNonce('mynonce');

        $this->r->addAssets('foo.css', 'foo.js', '/bar', '/foobar');
        $this->r->addInlineAssets(
            ['css' => 'CssTest'],
            ['js' => 'JsTest'],
            ['head' => '<script type="application/javascript">console.log(1)</script><style>body{margin:0}</style>']
        );

        $html = $this->r->renderHead();
        $this->assertStringContainsString('<style type="text/css" nonce="mynonce">CssTest</style>', $html);
        $this->assertStringContainsString('<script type="text/javascript" nonce="mynonce">JsTest</script>', $html);
        $this->assertStringContainsString(
            '<script nonce="mynonce" type="application/javascript">console.log(1)</script>',
            $html
        );
        $this->assertStringContainsString('<style nonce="mynonce">body{margin:0}</style>', $html);
    }

    public function testRenderFullInitialization(): void
    {
        $this->debugbar->addCollector(new \DebugBar\DataCollector\MessagesCollector());
        $this->r->addControl('time', ['icon' => 'time', 'map' => 'time', 'default' => '"0s"']);
        $expected = str_replace("\r\n", "\n", rtrim(file_get_contents(__DIR__ . '/full_init.html')));
        $this->assertStringStartsWith($expected, $this->r->render());
    }

    public function testRenderFullWithCsp(): void
    {
        $this->r->setCspNonce('mynonce');
        $this->assertStringStartsWith('<script type="text/javascript" nonce="mynonce">', $this->r->render());
    }

    public function testRenderConstructorOnly(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setJavascriptClass('Foobar');
        $this->r->setVariableName('foovar');
        $this->r->setAjaxHandlerClass(null);
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar foovar = new Foobar();\nfoovar.addDataSet(", $this->r->render());
    }

    public function testRenderConstructorWithNonce(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setCspNonce('mynonce');
        $this->assertStringStartsWith("<script type=\"text/javascript\" nonce=\"mynonce\">\nvar phpdebugbar = new PhpDebugBar.DebugBar();", $this->r->render());
    }

    public function testRenderConstructorWithEmptyTabsHidden(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setHideEmptyTabs(true);
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar phpdebugbar = new PhpDebugBar.DebugBar({\"hideEmptyTabs\":true});\n", $this->r->render());
    }

    public function testRenderConstructorWithTheme(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setTheme('dark');
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar phpdebugbar = new PhpDebugBar.DebugBar({\"theme\":\"dark\"});", $this->r->render());
    }

    public function testCanDisableSpecificVendors(): void
    {
        $this->assertStringContainsString('highlight.pack.js', $this->r->renderHead());
        $this->r->disableVendor('highlightjs');
        $this->assertStringNotContainsString('highlight.pack.js', $this->r->renderHead());
    }
}
