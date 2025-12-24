# Upgrade Guide 

## 2.x to 3.x

### Removed Bridge collectors

Version 3.x removes bridge collectors for Twig, Doctrine, Propel, CacheCache and Slim. 
Doctrine can be installed with: https://github.com/php-debugbar/doctrine-bridge
Twig Bridge can be installed with: https://github.com/php-debugbar/twig-bridge

This makes it easier to updates these collectors for specific versions.


### Remove obsolete methods
 - Removed get/setBindAjaxHandlerToJquery (Use bind to fetch/xhr instead)
 - Removed Assetic collection (use getAssets() directly if needed) 
 - Removed RequireJS support
 - Removed captureVar and renderCapturedVar from DebugBarVarDumper


### Other changes
 - Storage now uses json instead of serialize, so old data cannot be read.