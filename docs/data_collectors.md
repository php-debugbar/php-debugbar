# Collecting Data

## Using collectors

Collectors can be added to your debug bar using `addCollector()`.

    $debugbar = new DebugBar();
    $debugbar->addCollector(new DataCollector\RequestDataCollector());

Each collector as a unique name as defined by its `getName()` method. You can
access collectors using `getCollector($name)`.

    $debugbar->addCollector(new DataCollector\MessagesCollector());
    $debugbar->getCollector('messages')->addMessage("foobar");
    // or:
    $debugbar['messages']->addMessage("foobar");

Data will be collected from them when the debug bar is rendered. You can however
collect the data earlier using `collect()`.

    $debugbar->collect();

## Creating collectors

Collectors must implement the `DebugBar\DataCollector\DataCollectorInterface`. They
may subclass `DebugBar\DataCollector\DataCollector` which provides utility methods.

Collectors must provide a `getName()` function returning their unique name and a
`collect()` function returning some json-encodable data. The latter will be called at the
same time the `DebugBar::collect()` method is called.

    class MyDataCollector extends DebugBar\DataCollector\DataCollector
    {
        public function collect()
        {
            return array("uniqid" => uniqid());
        }

        public function getName()
        {
            return 'mycollector';
        }
    }

    $debugbar->addCollector(new MyDataCollector());

This however won't show anything in the debug bar as no information are provided
on how to display these data. You could do that manually as you'll see in later chapter
or implement the `DebugBar\DataCollector\Renderable` interface.

To implement it, you must define a `getWidgets()` function which returns an array
of key/value pairs where key are control names and values control options as defined
in `JavascriptRenderer::addControl($name, $options)` (see Rendering chapter).

    class MyDataCollector extends DebugBar\DataCollector\DataCollector implements DebugBar\DataCollector\Renderable
    {
        // ...

        public function getWidgets()
        {
            return array(
                "mycollector" => array(
                    "icon" => "cogs",
                    "tooltip" => "uniqid()",
                    "map" => "uniqid",
                    "default" => "''"
                )
            );
        }
    }

This will have the result of adding a new indicator to the debug bar.

When implementing the `Renderable` interface, you may use widgets which are not provided
with the default install. You can add new assets by implementing the `DebugBar\DataCollector\AssetProvider` interface.

To implement it, you must define the `getAssets()` method. It must return an array with the
following keys:

 - `base_path`: base path of assets (optional, if omitted or null, will use the base path of the `JavascriptRenderer`)
 - `base_url`: base url of assets (optional, same as `base_path`)
 - `css`: an array of css filenames
 - `js`: an array of javascript filenames
 - `inline_css`: an array map of content ID to inline CSS content (not including `<style>` tag)
 - `inline_js`: an array map of content ID to inline JS content (not including `<script>` tag)
 - `inline_head`: an array map of content ID to arbitrary inline HTML content (typically
   `<style>`/`<script>` tags); it will be embedded within the `<head>` element

All keys are optional.

Ideally, you should store static assets in filenames that are returned via the normal `css`/`js`
keys.  However, the inline asset elements are useful when integrating with 3rd-party
libraries that require static assets that are only available in an inline format.

The inline content arrays require special string array keys to identify the content:  the debug bar
will use them to deduplicate.  This is particularly useful if multiple instances of the same asset
provider are used.  Inline assets from all collectors are merged together into the same array,
so these content IDs effectively deduplicate the inline assets.

Example:

    class MyDbCollector extends DebugBar\DataCollector\DataCollector implements DebugBar\DataCollector\Renderable, DebugBar\DataCollector\AssetProvider
    {
        // ...

        public function getWidgets()
        {
            return array(
                "database" => array(
                    "icon" => "inbox",
                    "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                    "map" => "pdo",
                    "default" => "[]"
                )
            );
        }

        public function getAssets()
        {
            return array(
                'css' => 'widgets/sqlqueries/widget.css',
                'js' => 'widgets/sqlqueries/widget.js',

                // Ordinarily, inline assets like these should be avoided whenever possible:
                'inline_css' => array(
                    'db_widget_css' => 'div.myelement { color: #000; }',
                ),
                'inline_js' => array(
                    'db_widget_js' => 'alert("Db widget asset loaded.");'
                ),
                'inline_head' => array(
                    'db_widget_head' => '<meta content="Arbitrary HTML content">'
                )
            );
        }
    }
