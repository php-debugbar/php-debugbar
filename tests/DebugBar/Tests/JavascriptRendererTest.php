<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\JavascriptRenderer;

class JavascriptRendererTest extends DebugBarTestCase
{
    /** @var JavascriptRenderer  */
    protected $r;

    public function setUp(): void
    {
        parent::setUp();
        $this->r = new JavascriptRenderer($this->debugbar);
        $this->r->setBasePath('/bpath');
        $this->r->setBaseUrl('/burl');
        $this->r->setUseDistFiles(false);
    }

    public function testOptions()
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

    public function testAddAssets()
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

    public function testGetAssets()
    {
        $assets = $this->r->getAssets();
        $this->assertContains('/bpath/debugbar.css', $assets['css']);
        $this->assertContains('/bpath/widgets.js', $assets['js']);
        $this->assertContains('/bpath/vendor/highlightjs/highlight.pack.js', $assets['js']);
    }

    public function testGetAssetsExludeVendors()
    {
        $this->r->setIncludeVendors(false);
        $js = $this->r->getAssets('js');
        $this->assertContains('/bpath/debugbar.js', $js);
        $this->assertNotContains('/bpath/vendor/highlightjs/highlight.pack.js', $js);
    }

    public function testGetDistAssets()
    {
        $this->r->setUseDistFiles(true);
        $assets = $this->r->getAssets();
        $this->assertContains('/bpath/../dist/debugbar.min.css', $assets['css']);
        $this->assertNotContains('/bpath/widgets.js', $assets['js']);
        $this->assertNotContains('/bpath/vendor/highlightjs/highlight.pack.js', $assets['js']);
    }

    public function testRenderHead()
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

    public function testRenderFullInitialization()
    {
        $this->debugbar->addCollector(new \DebugBar\DataCollector\MessagesCollector());
        $this->r->addControl('time', ['icon' => 'time', 'map' => 'time', 'default' => '"0s"']);
        $expected = str_replace("\r\n", "\n", rtrim(file_get_contents(__DIR__ . '/full_init.html')));
        $this->assertStringStartsWith($expected, $this->r->render());
    }

    public function testRenderConstructorOnly()
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setJavascriptClass('Foobar');
        $this->r->setVariableName('foovar');
        $this->r->setAjaxHandlerClass(false);
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar foovar = new Foobar();\nfoovar.addDataSet(", $this->r->render());
    }

    public function testRenderConstructorWithNonce()
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setCspNonce('mynonce');
        $this->assertStringStartsWith("<script type=\"text/javascript\" nonce=\"mynonce\">\nvar phpdebugbar = new PhpDebugBar.DebugBar();", $this->r->render());
    }

    public function testRenderConstructorWithEmptyTabsHidden()
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setHideEmptyTabs(true);
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar phpdebugbar = new PhpDebugBar.DebugBar({\"hideEmptyTabs\":true});\n", $this->r->render());
    }

    public function testRenderConstructorWithTheme()
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setTheme('dark');
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar phpdebugbar = new PhpDebugBar.DebugBar({\"theme\":\"dark\"});", $this->r->render());
    }

    public function testCanDisableSpecificVendors()
    {
        $this->assertStringContainsString('highlight.pack.js', $this->r->renderHead());
        $this->r->disableVendor('highlightjs');
        $this->assertStringNotContainsString('highlight.pack.js', $this->r->renderHead());
    }
}
