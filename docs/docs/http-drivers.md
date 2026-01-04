# HTTP drivers

Some features of the debug bar requires sending http headers or
using the session. Many frameworks implement their own mechanism
on top of PHP native features.

To make integration with other frameworks as easy as possible,
the `DebugBar` object uses an instance of `DebugBar\HttpDriverInterface`
to access those features.

`DebugBar\PhpHttpDriver`, which uses native PHP mechanisms, is provided
and will be used if no other driver are specified.

## Symfony Http Driver

The is also a Symfony Http Driver, which uses the Symfony HttpFoundation Session and Response objects.

```php

// Get the Session from the framework, or create a new one
$session = new Session(new PhpBridgeSessionStorage());

$httpDriver = new SymfonyHttpDriver($session);
$debugbar->setHttpDriver($httpDriver);


// Get the Response from your application, before sending it
$response = ..

// Attach the response to be able to add headers.
$httpDriver->setResponse($response);

// Collect the data and send or inject it in the response
if ($isAjaxRequest) {
    $debugbar->sendDataInHeaders();
} else {
    $debugbar->getJavascriptRenderer()->injectInSymfonyResponse($response);
}

```