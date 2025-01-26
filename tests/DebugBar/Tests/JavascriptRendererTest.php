<?php

namespace DebugBar\Tests;

use DebugBar\JavascriptRenderer;
use DebugBar\DebugBar;
use DebugBar\StandardDebugBar;

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
    }

    public function testOptions()
    {
        $this->r->setOptions(array(
            'base_path' => '/foo',
            'base_url' => '/foo',
            'include_vendors' => false,
            'javascript_class' => 'Foobar',
            'variable_name' => 'foovar',
            'initialization' => JavascriptRenderer::INITIALIZE_CONTROLS,
            'enable_jquery_noconflict' => true,
            'controls' => array(
                'memory' => array(
                    "icon" => "cogs",
                    "map" => "memory.peak_usage_str",
                    "default" => "'0B'"
                )
            ),
            'disable_controls' => array('messages'),
            'ignore_collectors' => 'config',
            'ajax_handler_classname' => 'AjaxFoo',
            'ajax_handler_bind_to_jquery' => false,
            'ajax_handler_auto_show' => false,
            'open_handler_classname' => 'OpenFoo',
            'open_handler_url' => 'open.php'
        ));

        $this->assertEquals('/foo', $this->r->getBasePath());
        $this->assertEquals('/foo', $this->r->getBaseUrl());
        $this->assertFalse($this->r->areVendorsIncluded());
        $this->assertEquals('Foobar', $this->r->getJavascriptClass());
        $this->assertEquals('foovar', $this->r->getVariableName());
        $this->assertEquals(JavascriptRenderer::INITIALIZE_CONTROLS, $this->r->getInitialization());
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
        $controls = $this->r->getControls();
        $this->assertCount(2, $controls);
        $this->assertArrayHasKey('memory', $controls);
        $this->assertArrayHasKey('messages', $controls);
        $this->assertNull($controls['messages']);
        $this->assertContains('config', $this->r->getIgnoredCollectors());
        $this->assertEquals('AjaxFoo', $this->r->getAjaxHandlerClass());
        $this->assertFalse($this->r->isAjaxHandlerBoundToJquery());
        $this->assertFalse($this->r->isAjaxHandlerAutoShow());
        $this->assertEquals('OpenFoo', $this->r->getOpenHandlerClass());
        $this->assertEquals('open.php', $this->r->getOpenHandlerUrl());
    }

    public function testAddAssets()
    {
        // Use a loop to test deduplication of assets
        for ($i = 0; $i < 2; ++$i) {
            $this->r->addAssets('foo.css', 'foo.js', '/bar', '/foobar');
            $this->r->addInlineAssets(array('Css' => 'CssTest'), array('Js' => 'JsTest'), array('Head' => 'HeaderTest'));
        }

        // Make sure all the right assets are returned by getAssets
        list($css, $js, $inline_css, $inline_js, $inline_head) = $this->r->getAssets();
        $this->assertContains('/bar/foo.css', $css);
        $this->assertContains('/bar/foo.js', $js);
        $this->assertEquals(array('Css' => 'CssTest'), $inline_css);
        $this->assertEquals(array('Js' => 'JsTest'), $inline_js);
        $this->assertEquals(array('Head' => 'HeaderTest'), $inline_head);

        // Make sure asset files are deduplicated
        $this->assertCount(count(array_unique($css)), $css);
        $this->assertCount(count(array_unique($js)), $js);

        $html = $this->r->renderHead();
        $this->assertStringContainsString('<script type="text/javascript" src="/foobar/foo.js"></script>', $html);
    }

    public function testGetAssets()
    {
        list($css, $js) = $this->r->getAssets();
        $this->assertContains('/bpath/debugbar.css', $css);
        $this->assertContains('/bpath/widgets.js', $js);
        $this->assertContains('/bpath/vendor/jquery/dist/jquery.min.js', $js);

        $this->r->setIncludeVendors(false);
        $js = $this->r->getAssets('js');
        $this->assertContains('/bpath/debugbar.js', $js);
        $this->assertNotContains('/bpath/vendor/jquery/dist/jquery.min.js', $js);
    }

    public function testRenderHead()
    {
        $this->r->addInlineAssets(array('Css' => 'CssTest'), array('Js' => 'JsTest'), array('Head' => 'HeaderTest'));

        $html = $this->r->renderHead();
        // Check for file links
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="/burl/debugbar.css">', $html);
        $this->assertStringContainsString('<script type="text/javascript" src="/burl/debugbar.js"></script>', $html);
        // Check for inline assets
        $this->assertStringContainsString('<style type="text/css">CssTest</style>', $html);
        $this->assertStringContainsString('<script type="text/javascript">JsTest</script>', $html);
        $this->assertStringContainsString('HeaderTest', $html);
        // Check jQuery noConflict
        $this->assertStringContainsString('jQuery.noConflict(true);', $html);

        // Check for absence of jQuery noConflict
        $this->r->setEnableJqueryNoConflict(false);
        $html = $this->r->renderHead();
        $this->assertStringNotContainsString('noConflict', $html);
    }

    public function testRenderFullInitialization()
    {
        $this->debugbar->addCollector(new \DebugBar\DataCollector\MessagesCollector());
        $this->r->addControl('time', array('icon' => 'time', 'map' => 'time', 'default' => '"0s"'));
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

    public function testJQueryNoConflictAutoDisabling()
    {
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
        $this->r->setIncludeVendors(false);
        $this->assertFalse($this->r->isJqueryNoConflictEnabled());
        $this->r->setEnableJqueryNoConflict(true);
        $this->r->setIncludeVendors('css');
        $this->assertFalse($this->r->isJqueryNoConflictEnabled());
        $this->r->setEnableJqueryNoConflict(true);
        $this->r->setIncludeVendors(array('css', 'js'));
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
    }

    public function testCanDisableSpecificVendors()
    {
        $this->assertStringContainsString('jquery.min.js', $this->r->renderHead());
        $this->r->disableVendor('jquery');
        $this->assertStringNotContainsString('jquery.min.js', $this->r->renderHead());
    }
}
