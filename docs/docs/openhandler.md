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
