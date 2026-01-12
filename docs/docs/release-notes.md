# Release Notes

## Version 3.x

Debugbar 3.x brings a lot of new features and improvements:

 - Modernize Javascript, removed jQuery
 - New Tabler icons with build script, replaces heavy FontAwesome with minimal SVG icons
 - Build scripts for prefixed dependencies and minimized assets
 - New AssetHandler to make to make it easier to dump assets into your own project
 - Improved contrast for Light mode
 - Option to render the debugbar on top instead of bottom
 - Render widget on open to reduce initial page load time
 - Improved TimelineData support to other collectors
 - Backtrace support for QueryCollector
 - Static analysis with phpstan/eslint, typehints everywhere
 - Added Symfony HttpFoundation Request collector (with bridge)
 - Improved performance and a lot more tweaks..

### New/removed/moved bridge Collectors
 - Version 3.x removes bridge collectors for Propel, CacheCache and Slim.
 - Doctrine has been moved to: https://github.com/php-debugbar/doctrine-bridge
 - Twig Bridge has been moved to: https://github.com/php-debugbar/twig-bridge
 - Monolog Bridge has been moved to: https://github.com/php-debugbar/monolog-bridge
 - Symfony Mailer and new Symfony RequestCollector and HttpDriver: https://github.com/php-debugbar/symfony-bridge

### Changes to widgets
- jQuery is removed, and widgets are now Javascript classes. Custom widgets should be updated.
- FontAwesome is removed, and replaced by SVG icons from Tabler, included in CSS. Only the icons used by the default widgets are included, so packages extending the debugbar should add their own icons.
- Typehints are added to all widgets, so you might need to update your widgets.
- Widgets are rendered when opening a tab, not when loading the page.

### Changes to DataCollectors
- TimeDataCollector is removed from the constructors, but a setTimeDataCollector method is added.
- useHtmlVarDumper is removed. The HtmlDataFormatter is used by default. To use plain-text, the the default formatter to DataFormatter.

### Remove obsolete methods
- Removed get/setBindAjaxHandlerToJquery (Use bind to fetch/xhr instead)
- Removed Assetic collection (use getAssets() directly if needed)
- Removed RequireJS support
- Removed captureVar and renderCapturedVar from DebugBarVarDumper

### Breaking changes to methods/interfaces
- Everything is typehinted, so you might need to update your code for custom collectors.
- getAssets() removed the `$type` parameter and always returns all assets.
- OpenHandler requires the `op` parameter to be always set.
- The DataFormatterInterface has a 2nd 'deep' parameter to formatVar.
- The StorageInterface has a new 'prune' method

### Other changes
- Storage now uses json instead of serialize, so old data cannot be read.
- PDO now quotes using the PDO connection when available. The quotation char is now always `'`. Methods have moved to the QueryFormatter instead of TracedStatement.
- The DataFormatterInterface has a 2nd 'deep' parameter to formatVar.
