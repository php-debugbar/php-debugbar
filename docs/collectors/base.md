
# Base collectors

Collectors provided in the `DebugBar\DataCollector` namespace.

## Messages

Provides a way to log messages (compatible with [PSR-3 logger](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)).
You can call the `useHtmlVarDumper()` function to use VarDumper's interactive HTML dumper for
interactively rendering complex variables.  If you do that, you must properly render
[inline assets](../docs/rendering.md#assets) when rendering the debug bar in addition to the normal js/css
static assets.
```php
$c = new DebugBar\DataCollector\MessagesCollector();
$c->useHtmlVarDumper(); // Enables prettier dumps of objects; requires inline assets
$debugbar->addCollector($c);

$debugbar['messages']->info('hello world');
$complicated_variable = array(1, 2, array(3, 4));
$debugbar['messages']->info($complicated_variable); // interactive HTML variable dumping
```

You can have multiple messages collector by naming them:
```php
$debugbar->addCollector(new MessagesCollector('io_ops'));
$debugbar['io_ops']->info('opening files');
```

You can aggregate messages collector into other to have a unified view:
```php
$debugbar['messages']->aggregate($debugbar['io_ops']);
```

If you don't want to create a standalone tab in the debug bar but still be able
to log messages from a different collector, you don't have to add the collector
to the debug bar:
```php
$debugbar['messages']->aggregate(new MessagesCollector('io_ops'));
```

## TimeData

Provides a way to log total execution time as well as taking "measures" (ie. measure the execution time of a particular operation).
```php
$debugbar->addCollector(new DebugBar\DataCollector\TimeDataCollector());

$debugbar['time']->startMeasure('longop', 'My long operation');
sleep(2);
$debugbar['time']->stopMeasure('longop');

$debugbar['time']->measure('My long operation', function() {
    sleep(2);
});
```

Displays the measures on a timeline

## Exceptions

Display exceptions
```php
$debugbar->addCollector(new DebugBar\DataCollector\ExceptionsCollector());

try {
    throw new Exception('foobar');
} catch (Exception $e) {
    $debugbar['exceptions']->addThrowable($e);
}
```

## PDO

Logs SQL queries.
```php
$debugbar->addCollector(new DebugBar\DataCollector\PDO\PDOCollector($pdo));
```

You can even log queries from multiple `PDO` connections:
```php
$pdoCollector = new DebugBar\DataCollector\PDO\PDOCollector();
$pdoCollector->addConnection($pdoRead, 'read-db');
$pdoCollector->addConnection($pdoWrite, 'write-db');

$debugbar->addCollector($pdoCollector);
```
    
If you want to see your PDO requests in the TimeDataCollector, you must add the PDOConnector to the $debugbar _first_
```php
$timeDataCollector = new DebugBar\DataCollector\TimeDataCollector();
$pdoCollector = new DebugBar\DataCollector\PDO\PDOCollector($pdo, $timeDataCollector);

$debugBar->addCollector($pdoCollector);
$debugBar->addCollector($timeDataCollector);
```

You can enable backtrace to show where the query is generated from, with the amount of frames to show as optional argument.
```php
$pdoCollector = new DebugBar\DataCollector\PDO\PDOCollector($pdo);
$pdoCollector->enableBacktrace(15);
```

By default, the PDO Collector replaces bindings in the queries. This can be disabled:

```php
$pdoCollector = new DebugBar\DataCollector\PDO\PDOCollector($pdo);
$pdoCollector->setRenderSqlWithParams(false);
```

## RequestData

Collects the data of PHP's global variables.  You can call the `useHtmlVarDumper()` function to use
VarDumper's interactive HTML dumper for rendering the variables.  If you do that, you must properly
render [inline assets](../docs/rendering.md#assets) when rendering the debug bar in addition to the normal
js/css static assets.
```php
$requestDataCollector = new DebugBar\DataCollector\RequestDataCollector();
$requestDataCollector->useHtmlVarDumper();
$debugbar->addCollector($requestDataCollector);
```

## Config

Used to display any key/value pairs array.  You can call the `useHtmlVarDumper()` function to use
VarDumper's interactive HTML dumper for rendering the variables.  If you do that, you must properly
render [inline assets](../docs/rendering.md#assets) when rendering the debug bar in addition to the normal
js/css static assets.
```php
$data = array('foo' => 'bar');
$configCollector = new DebugBar\DataCollector\ConfigCollector($data);
$configCollector->useHtmlVarDumper();
$debugbar->addCollector($configCollector);
```

You can provide a different name for this collector in the second argument of the constructor.

## AggregatedCollector

Aggregates multiple collectors. Do not provide any widgets, you have to add your own controls.
```php
$debugbar->addCollector(new DebugBar\DataCollector\AggregatedCollector('all_messages', 'messages', 'time'));
$debugbar['all_messages']->addCollector($debugbar['messages']);
$debugbar['all_messages']->addCollector(new DebugBar\DataCollector\MessagesCollector('mails'));
$debugbar['all_messages']['mails']->addMessage('sending mail');

$renderer = $debugbar->getJavascriptRenderer();
$renderer->addControl('all_messages', array(
    'widget' => 'PhpDebugBar.Widgets.MessagesWidget',
    'map' => 'all_messages',
    'default' => '[]',
));
```

## Others

Misc collectors which you can just register:

 - `MemoryCollector` (*memory*): Display memory usage
 - `PhpInfoCollector` (*php*): PHP version number
