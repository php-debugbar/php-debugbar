# Open handler

The debug bar can open previous sets of collected data which were stored using
a storage handler (see previous section). To do so, it needs to be provided an
url to an open handler.

An open handler must respect a very simple protocol. The default implementation
is `DebugBar\OpenHandler`.
```php
$openHandler = new DebugBar\OpenHandler($debugbar);
$openHandler->handle();
```

Calling `handle()` will use data from the `$_REQUEST` array and echo the output.
The function also supports input from other source if you provide an array as
first argument. It can also return the data instead of echoing (use false as
second argument) and not send the content-type header (use false as third argument).

One you have setup your open handler, tell the `JavascriptRenderer` its url.
```php
$renderer->setOpenHandlerUrl('open.php');
```

This adds a button in the top right corner of the debug bar which allows you
to browse and open previous sets of collected data.

## Request summaries

The open handler also serves a plain text summary of a stored dataset, for tooling that
never renders the bar - a terminal, a CI job, or an agent driving the app over HTTP.

```
GET open.php?op=summary&id=<dataset id>
```

Omit `id` to summarize the most recently stored dataset. The response is
`text/plain`, and contains the same text the bar's summary popover shows under "All":

```
# PHP DebugBar summary

id = 01a01ecb81c6571ca7a79f91aae2ae9d
datetime = 2026-01-30 10:50:48

## Request
method = GET
uri = /checkout?page=2&api_token=ab***op
status = 200

## Database
statements = 8
duration = 135us
duplicates = 2
```

The dataset id of the current request is also sent as the `phpdebugbar-id` response
header (see [Ajax and stacked data](ajax-and-stack.md)), so a client can read the
header and fetch the matching summary.

The same text is available in PHP through `$debugbar->getSummary()`, and in the browser
through `PhpDebugBar.instance.getSummary()`.
