# Changelog
2025-06-26

 - Add Windsurf editor link template

2023-09-08

 - Add SymfonyMailCollector (#554)

2021-12-21

 - Add support for `symfony/var-dumper^6` package

2019-05 (1.10.3)

- New implementation for `dump()` in SwiftLogCollector (#265)

2014-12 (1.10.2):

 - Use Symfony VarDumper instead of kintLite as DataFormatter (#179)
 - Better resize handling (#185)

2014-11 (1.10.1):

 - Add disableVendor() option to JavascriptRenderer to remove a specific vendor (#182)
 - Fix macros in Twig Collector (#167, #177)
 - Update Font Awesome to 4.2.0

2014-10 (1.10.0):

 - Add bindToXHR() as alternative to jQuery ajax handling.
 - Extend TemplateWidget to show more information + parameters
 - Extend TimeDataCollector to show parameters + collector source

2014-08:

 - Replace image files with inline data in css
 - Tweak OpenHandler display

2014-06-10:

 - Add LocalizationCollector

2014-03-29:

 - Add hasMeasure() method to TimeDataCollector

2014-03-25:

 - Duplicate SQL detection

2014-03-23:

 - Add syntax highlighting

2014-03-22:

 - added AssetProvider interface
 - added JavascriptRenderer::addAssets()
 - added Memcached storage

2014-01-04:

 - added DataFormatter

2013-12-29:

 - display an error where the header is too large when sending data with an ajax request
 - close button instead of minimize

2013-12-27:

 - responsiveness of the debugbar in the browser

2013-12-19:

 - use Bower to manage assets

2013-10-24:

 - added option to render sql with params in PDO collector

2013-10-06:

 - prefixed all css classes
 - new PhpDebugBar.utils.csscls() function
 - changed datetime to time in datasets selector
 - close and open buttons now uses images instead of font-awesome

2013-09-23:

 - send the request id in headers and use the open handler to retreive the dataset
 - !! modified sendDataAsHeaders() to add $useOpenHandler as the first argument
 - OpenHandler::handle() can now take any objects implementing ArrayAccess as request data

2013-09-19:

 - added HttpDriver
 - added jQuery.noConflict() managment

2013-09-15 (1.6):

 - added sending data through HTTP headers
 - added stacked data
 - bug fixes

1.5:

 - added storage
 - added open handler
