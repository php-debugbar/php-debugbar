# Release Notes

## Version 3.x

Debugbar 3.x brings a lot of new features and improvements:

 - Modernize Javascript, removed jQuery
 - New Tabler icons with build script, replaces heavy FontAwesome with minimal SVG icons
 - Build scripts for prefixed dependencies and minimized assets
 - New AssetHandler to make to make it easier to dump assets into your own project
 - Improved contrast for Light mode
 - Option to render the debugbar on top instead of bottom
 - Backtrace support for QueryCollector
 - Static analysis with phpstan/eslint
 - Improved performance and a lot of tweaks..

### Removed/moved bridge Collectors
 - Version 3.x removes bridge collectors for Propel, CacheCache and Slim.
 - Doctrine has been moved to: https://github.com/php-debugbar/doctrine-bridge
 - Twig Bridge has been moved to: https://github.com/php-debugbar/twig-bridge

### Changes to widgets
- jQuery is removed, and widgets are now Javascript classes. Custom widgets should be updated.
- FontAwesome is removed, and replaced by SVG icons from Tabler, included in CSS. Only the icons used by the default widgets are included, so packages extending the debugbar should add their own icons.
- Typehints are added to all widgets, so you might need to update your widgets.

### Remove obsolete methods
- Removed get/setBindAjaxHandlerToJquery (Use bind to fetch/xhr instead)
- Removed Assetic collection (use getAssets() directly if needed)
- Removed RequireJS support
- Removed captureVar and renderCapturedVar from DebugBarVarDumper

### Other changes
- Storage now uses json instead of serialize, so old data cannot be read.
- PDO now quotes using the PDO connection when available. The quotation char is now always `'`. Methods have moved to the QueryFormatter instead of TracedStatement.