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

The AjaxHandler automatically binds to `fetch()` and `XMLHttpRequest` events, which will detect AJAX requests and add the data to the debugbar.

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

## Streamed responses

Debugbar normally attaches its data to a response through the `phpdebugbar-id`
response header. Streamed responses (SSE, `StreamedResponse`, or anything that
flushes output mid-request) commit their HTTP headers on the first flush, so
that header is lost and the AJAX handler can't load the dataset.

To work around this, the AJAX handler can add a `phpdebugbar-request-id`
correlation header to every **same-origin** `fetch()`/`XMLHttpRequest` (a random
id it generates client-side). PHP stores that id under the `rid` meta key, and
when no `phpdebugbar-id` response header comes back, the client looks the stored
dataset up through the open handler by its `rid`. This requires a storage +
open handler to be configured (see above).

The feature is **off by default**. Enable it through the `JavascriptRenderer`:
```php
$renderer = $debugbar->getJavascriptRenderer();
$renderer->setAjaxHandlerCaptureStreamed(true);

// optional: which response Content-Types are treated as streamed
// (defaults to ['text/event-stream'])
$renderer->setAjaxHandlerStreamedContentTypes(['text/event-stream', 'application/x-ndjson']);
```
Both are also available through `setOptions()` as
`ajax_handler_capture_streamed` and `ajax_handler_streamed_content_types`.

A few notes:

- Injection is gated on same-origin only. Adding a custom header to a
  cross-origin request would trigger a CORS preflight and break third-party
  API calls, so cross-origin requests are never touched.
- Because the header is added to *every* same-origin request, every stored
  request gains a `rid` in its `__meta`. This is harmless — it is only
  *consumed* as a fallback when the response header is absent.
- The fallback lookup only runs for responses whose `Content-Type` is in
  `streamedContentTypes` (default `['text/event-stream']`), so normal responses
  never trigger an extra open handler query. Matching is on the base media type,
  so `text/event-stream; charset=utf-8` is accepted. Set the list to `null`/`[]`
  to fall back on any response missing the id header.
- The client-side toggles map to `ajaxHandler.captureStreamed` and
  `ajaxHandler.streamedContentTypes`, which can also be set directly in JS.
- This only covers requests that can set a request header (`fetch`/
  `XMLHttpRequest`). `EventSource`/SSE cannot set headers and is not correlated.

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
