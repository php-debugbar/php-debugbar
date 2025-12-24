# Data Formatter

## HTML variable formatting

PHP Debug Bar integrates with Symfony's
[VarDumper](https://symfony.com/doc/current/components/var_dumper.html) component to provide
interactive HTML-based variable dumps. This is accomplished via the
`DebugBar\DataFormatter\DebugBarVarDumper` class, which wraps VarDumper functionality for use by the
debug bar.

Debug bar users who wish to take advantage of this feature must ensure that they properly render
[inline assets](rendering.html#assets) when rendering the debug bar. That's because collectors
using the variable dumper return the static assets of the HTML variable dumper, which includes
inline assets.

By default, collectors inheriting from `DebugBar\DataCollector\DataCollector` will use the
`DebugBarVarDumper` instance specified by the static `DataCollector::setDefaultVarDumper` function.
This can be overridden on a per-collector basis by the non-static `DataCollector::setVarDumper`
function.

    // Modify default options used globally by all collectors
    DataCollector::getDefaultVarDumper()->mergeClonerOptions(array(
        'max_items' => 50,
    ));

    // Modify options for a specific collector
    $varDumper = new DebugBarVarDumper();
    $varDumper->mergeDumperOptions(array(
        'max_string' => 100,
    ));
    $collector->setVarDumper($varDumper);

VarDumper has two key classes that are used by `DebugBarVarDumper`. The options can be set using
the `mergeClonerOptions`, `resetClonerOptions`, `mergeDumperOptions`, and `resetDumperOptions`
methods on `DebugBarVarDumper`.

 - `VarCloner`: Cloners copy the contents of a variable into a serializable format. They are
   intended to run as fast as possible; advanced rendering/formatting is saved for the dumper.
   Classes known as casters control how particular data types are serialized; if no caster exists,
   then a generic serialization is done. You can specify custom casters using the
   `additional_casters` option; the default list of casters can be overridden with the `casters`
   option. Finally, the number of items and maximum string length to clone can be controlled via the
   `max_items`, `min_depth`, and `max_string` options; consult the
   [VarDumper documentation](https://symfony.com/doc/current/components/var_dumper/advanced.html)
   for more information on these options, which have a considerable performance impact. Note that
   the `min_depth` option requires VarDumper 3.4 or newer.
 - `HtmlDumper`: Dumpers format cloned data for a particular destination, such as command-line or
   HTML. `DebugBarVarDumper` only uses the `HtmlDumper`. Custom styles can be specified via the
   `styles` option, but this is not generally needed. If using VarDumper 3.2 or newer, you may also
   specify the `expanded_depth`, `max_string`, and `file_link_format` options. `expanded_depth`
   controls the tree depth that should be expanded by default upon initial rendering. `max_string`
   can be used to truncate strings beyond the initial truncation done by the cloner.
   `file_link_format` is a format string used to generate links to source code files.

A collector wishing to take advantage of this feature must call the `renderVar()` function and
return the HTML result as part of the request dataset:

    public function collectVariable($v)
    {
        // This will clone and then dump the variable in one operation:
        $this->variableHtml = $this->getVarDumper()->renderVar($v);
    }

    public function collect()
    {
        return array('variableHtml' => $this->variableHtml);
    }

The collector may then render the raw HTML in a Javascript widget:

    if (value.variableHtml) {
        var val = $('<span />').html(value.variableHtml).appendTo(otherElement);
    }

If the collector takes advantage of the variable dumper, as shown above, then it must also
implement the `AssetProvider` interface and include the assets of the variable dumper. This does
not take place by default, because not all collectors will use the variable dumper.

    class MyCollector extends DataCollector implements Renderable, AssetProvider
    {
        public function getAssets() {
            return $this->getVarDumper()->getAssets();
        }
    }

## Text formatting

An instance of `DebugBar\DataFormatter\DataFormatterInterface` is used by collectors to
format variables into a text-only format.

The default instance is `DebugBar\DataFormatter\DataFormatter`. This can be modified
using `DebugBar\DataCollector\DataCollector::setDefaultDataFormatter()`.

You can use a custom formatter for each collector using `DataCollector::setDataFormatter()`.

For general-purpose variable formatting, it's recommended to use the HTML variable dumper, described
earlier.
