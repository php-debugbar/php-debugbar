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

### SQLite

> **Warning: Never put the SQLite database in a publicly available directory!**


Stores data in a file-based SQLite database with automatic garbage collection.
This is ideal for development environments and applications that need persistent
storage without setting up a separate database server.

Make sure the file is outside of the web root directory. The file will be created automatically if it doesn't exist.

```php
$storage = new DebugBar\Storage\SqliteStorage('/path/to/debugbar.sqlite');
```

If the table exists, you can optionally specify the name of the table:

```php
$storage = new DebugBar\Storage\SqliteStorage(
    filepath: '/path/to/debugbar.sqlite',
    tableName: 'phpdebugbar',
);
```


## Automatic pruning of old entries

The storage automatically removes old entries based on the configured parameters:

- `autoPrune`: Number of hours to keep entries
- `autoPruneProbability`: Chance between 0-100 that GC runs on each save

You can set autoPrune to `false` to disable automatic pruning.

```php
$storage->setAutoPrune(72, 10); // Keep entries for 72 hours, with 10% chance
```

You can also manually trigger garbage collection:

```php
$storage->prune(24);    // Keep entries for 24 hours
```


## Creating your own storage

You can easily create your own storage handler by implementing the
`DebugBar\Storage\StorageInterface`.

## Request ID generator

For each request, the debug bar will generate a unique id under which to store the
collected data. This is perform using a `DebugBar\RequestIdGeneratorInterface` object.

If none are defined, the debug bar will automatically use `DebugBar\RequestIdGenerator`
which uses random bytes to generate the id.

The Request ID SHOULD be in lexical order and never be just numeric. 
