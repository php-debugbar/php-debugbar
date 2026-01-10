# Upgrade Guide 

## 2.x to 3.x

### Removed Bridge collectors

Version 3.x removes bridge collectors for Twig, Doctrine, Propel, CacheCache and Slim. 
Doctrine can be installed with: https://github.com/php-debugbar/doctrine-bridge
Twig Bridge can be installed with: https://github.com/php-debugbar/twig-bridge

This makes it easier to updates these collectors for specific versions.

### Changes to widgets
 - jQuery is removed, and widgets are now Javascript classes. Custom widgets should be updated.
 - FontAwesome is removed, and replaced by SVG icons from Tabler, included in CSS. Only the icons used by the default widgets are included, so packages extending the debugbar should add their own icons.
 - Typehints are added to all widgets, so you might need to update your widgets.

### Remove obsolete methods
 - Removed get/setBindAjaxHandlerToJquery (Use bind to fetch/xhr instead)
 - Removed Assetic collection (use getAssets() directly if needed) 
 - Removed RequireJS support
 - Removed captureVar and renderCapturedVar from DebugBarVarDumper

### Breaking changes to methods
- getAssets() removed the `$type` parameter and always returns all assets.
- OpenHandler requires the `op` parameter to be always set.
- useHtmlVarDumper is removed. The HtmlDataFormatter is used by default. To use plain-text, the the default formatter to DataFormatter.
- The DataFormatterInterface has a 2nd 'deep' parameter to formatVar.

### Other changes
 - Storage now uses json instead of serialize, so old data cannot be read.
 - PDO now quotes using the PDO connection when available. The quotation char is now always `'`. Methods have moved to the QueryFormatter instead of TracedStatement.
