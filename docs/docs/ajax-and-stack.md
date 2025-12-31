# AJAX and Stacked data

## AJAX

As mentioned in the previous chapter, if you are performing AJAX requests
which return HTML content, you can use `JavascriptRenderer::render(false)`.

In the case you are sending back non-HTML data (eg: JSON), the DebugBar can
send data to the client using HTTP headers using the `sendDataInHeaders()` method
(no need to use the `JavascriptRenderer`):
```php
$debugbar = new DebugBar();
// ...
$debugbar->sendDataInHeaders();
```

On the client side, an instance of `PhpDebugBar.AjaxHandler` will
parse the headers and add the dataset to the debugbar.

The AjaxHandler automatically binds to jQuery's *ajaxComplete* event
so if you are using jQuery, you have nothing to configure.

If you're not using jQuery, you can call `AjaxHandler.handle(xhr)`.
If you are using the `JavascriptRenderer` initialization, the instance
of `AjaxHandler` is stored in the `ajaxHandler` property of the `DebugBar` object.
```js
debugbar.ajaxHandler.handle(xhr);
```

If you are sending a lot of data through headers, it may cause problems
with your browser. Instead you can use a storage handler (see Storage chapter)
and the open handler (see Open Handler chapter) to load the data after an ajax
request. Use true as the first argument of `sendDataInHeaders()`.
```php
$debugbar = new DebugBar();

// define a storage
$debugbar->setStorage(new DebugBar\Storage\FileStorage('/path/to/storage'));

// define the open handler url
$renderer = $debugbar->getJavascriptRenderer();
$renderer->setOpenHandlerUrl('open.php');

// ...

$debugbar->sendDataInHeaders(true);
```

By default, the debug bar will immediately show new AJAX requests. If your page
makes a lot of requests in the background (e.g. tracking), this can be
disruptive. You can disable this behavior by calling
`setAjaxHandlerAutoShow(false)` on the `JavascriptRenderer`, like this:
```php
$renderer = $debugbar->getJavascriptRenderer();
$renderer->setAjaxHandlerAutoShow(false);
```

## Fetch

Fetch API is supported by wrapping `window.fetch` so that the promise is also
passed through to the debugbar AJAX handler.

If you find your fetch requests are not showing up in debugbar, you're probably
initializing your JavaScript client library (e.g. Apollo) before debugbar has
loaded, try adding `defer` onto your script tags, or moving them after the
injected debugbar JavaScript.

## Stacked data

Some times you need to collect data about a request but the page won't actually
be displayed. The best example of that is during a redirect. You can use the
debug bar storage mechanism to store the data and re-open it later but it can
be cumbersome while testing a redirect page.

The solution is to use stacked data. The debug bar can temporarily store the
collected data in the session until the next time it will be displayed.
Simply call `DebugBar::stackData()` instead of rendering the debug bar.

PHP's session must be started before using this feature.

Note: The stacked data feature will use the storage mechanism if it's enabled
instead of storing the data in the session.
```php
$debugbar = new DebugBar();
// ...
$debugbar->stackData();
```

Stacked data are rendered each time the debug bar is rendered using the
`JavascriptRenderer`.
