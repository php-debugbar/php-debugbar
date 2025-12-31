# Storage

DebugBar supports storing collected data for later analysis.
You'll need to set a storage handler using `setStorage()` on your `DebugBar` instance.
```php
$debugbar->setStorage(new DebugBar\Storage\FileStorage('/path/to/dir'));
```

Each time `DebugBar::collect()` is called, the data will be persisted.

## Available storage

### File

It will collect data as json files under the specified directory
(which has to be writable).
```php
$storage = new DebugBar\Storage\FileStorage($directory);
```

### Redis

Stores data inside a Redis hash. Uses [Predis](http://github.com/nrk/predis).
```php
$storage = new DebugBar\Storage\RedisStorage($client);
```

### PDO

Stores data inside a database.
```php
$storage = new DebugBar\Storage\PdoStorage($pdo);
```

The table name can be changed using the second argument and sql queries
can be changed using `setSqlQueries()`.

## Creating your own storage

You can easily create your own storage handler by implementing the
`DebugBar\Storage\StorageInterface`.

## Request ID generator

For each request, the debug bar will generate a unique id under which to store the
collected data. This is perform using a `DebugBar\RequestIdGeneratorInterface` object.

If none are defined, the debug bar will automatically use `DebugBar\RequestIdGenerator`
which uses the `$_SERVER` array to generate the id.
