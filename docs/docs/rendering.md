# Rendering

Rendering is performed using the `DebugBar\JavascriptRenderer` class. It contains
all the useful functions to included the needed assets and generate a debug bar.
```php
$renderer = $debugbar->getJavascriptRenderer();
```

## Assets

The debug bar relies on some css and javascript files which needs to be included
into your webpage. They are located in the *resources* folder.
Additionally, asset providers may provide inline assets that have to be embedded
directly in the HTML.  This can be done in four ways:

 - Using `JavascriptRenderer::renderHead()` using `setBaseUrl()` to point to the asset folder directly
 - Using `JavascriptRenderer::renderHead()` using `setAssetHandlerUrl()` to point to the AssetHandler
 - Dumping the assets yourself using `JavascriptRenderer::dumpCssAssets()`,
   `JavascriptRenderer::dumpJsAssets()`, and `JavascriptRenderer::dumpHeadAssets()`.
 - Retrieving filenames and inline content of assets using `JavascriptRenderer::getAssets()`
   and doing something with it

You can define the base url of your assets using `setBaseUrl()`, or set the [AssetHandler](#asset-handler) with `setAssetHandlerUrl()` (see below).

Using `renderHead()`:
```php
<html>
    <head>
        ...
        <?php echo $renderer->renderHead() ?>
        ...
    </head>
    ...
</html>
```

Dumping the assets:
```php
header('Content-Type: text/javascript');
$renderer->dumpJsAssets();
```

Retrieving the assets:
```php
list($cssFiles, $jsFiles, $inlineCss, $inlineJs, $inlineHead) = $renderer->getAssets();
```

### Asset Handler

The AssetHandler can be used to serve assets from a different location than the default.

```php
$renderer->setAssetHandlerUrl('assets.php');
```

You can then implement an endpoint with the AssetHandler in your application to serve the assets from a different location.

```php
$openHandler = new DebugBar\AssetHandler($debugbar);
$openHandler->handle($_GET);
```

Make sure you add the same collectors to the DebugBar instance you use in the AssetHandler, as in your main application.
Otherwise not all assets will be available. The minified files usually include all assets.

### Vendor assets
Note that you can only use the debug bar assets and manage the dependencies by yourself
using `$renderer->setIncludeVendors(false)`. Instead of false, *css* or *js* may be used
to only include css or js assets of vendors.

### Minified dist files
By default, the debug bar uses the minified files from the dist folder. These are generated automatically and include alle vendor and widget files.
If you want to use the non-minified files, you can set `$renderer->useDistFiles(false)`.
When using dist files, any additional scripts or inline scripts/styles will still be included.

## The javascript object

The renderer will generate all the needed code for your debug bar. This means
initializing the DebugBar js object, adding tabs and indicators, defining a data map, etc...

Data collectors can provide their own controls when implementing the
`DebugBar\DataCollector\Renderable` interface as explained in the Collecting Data chapter.

Thus in almost all cases, you should only have to use `render()` right away:
```php
<html>
    ...
    <body>
        <?php echo $renderer->render() ?>
    </body>
</html>
```

This will print the initialization code for the toolbar and the dataset for the request.
When you are performing AJAX requests, you do not want to initialize a new toolbar but
add the dataset to the existing one. You can disable initialization using `false` as
the first argument of `render()`.
```php
<p>my ajax content</p>
<?php echo $renderer->render(false) ?>
```

### Controlling object initialization

You can further control the initialization of the javascript object using `setInitialization()`.
It takes a bitwise value made out of the constants `INITIALIZE_CONSTRUCTOR` and `INITIALIZE_CONTROLS`.
The first one controls whether to initialize the variable (ie. `var debugbar = new DebugBar()`). The
second one whether to initialize all the controls (ie. adding tab and indicators as well as data mapping).

You can also control the class name of the object using `setJavascriptClass()` and the name of
the instance variable using `setVariableName()`.

Let's say you have subclassed `PhpDebugBar.DebugBar` in javascript to do your own initialization.
Your new object is called `MyDebugBar`.
```php
$renderer->setJavascriptClass("MyDebugBar");
$renderer->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
// ...
echo $renderer->render();
```

This has the result of printing:
```html
<script type="text/javascript">
var phpdebugbar = new MyDebugBar();
phpdebugbar.addDataSet({ ... });
</script>
```

Using `setInitialization(0)` will only render the addDataSet part.

### Defining controls

Controls can be manually added to the debug bar using `addControl($name, $options)`. You should read
the Javascript bar chapter before this section.

`$name` will be the name of your control and `$options` is a key/value pair array with these
possible values:

- *icon*: icon name
- *tooltip*: string
- *widget*: widget class name
- *map*: a property name from the data to map the control to
- *default*: a js string, default value of the data map
- *tab*: class name of the tab object (to use a custom tab object)
- *indicator*: class name of the indicator object (to use a custom indicator object)
- *position*: position of the indicator ('left' of 'right', default to 'right')

At least *icon* or *widget* are needed (unless *tab* or *indicator* are specified). If *widget* is
specified, a tab will be created, otherwise an indicator. Any other options is also passed to the tab
or indicator.
```php
$renderer->addControl('messages', array(
    "widget" => "PhpDebugBar.Widgets.MessagesWidget",
    "map" => "messages",
    "default" => "[]"
));
```

You can disable a control using `disableControl($name)` and ignore any controls provided by
a collector using `ignoreCollector($name)`.
