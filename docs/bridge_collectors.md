# Bridge collectors

DebugBar comes with some "bridge" collectors. This collectors provides a way to integrate
other projects with the DebugBar.

## CacheCache

http://maximebf.github.io/CacheCache/

Displays cache operations using `DebugBar\Bridge\CacheCacheCollector`

    $cache = new CacheCache\Cache(new CacheCache\Backends\Memory());
    $debugbar->addCollector(new DebugBar\Bridge\CacheCacheCollector($cache));

CacheCache uses [Monolog](https://github.com/Seldaek/monolog) for logging,
thus it is required to collect data.

`CacheCacheCollector` subclasses `MonologCollector`, thus it can be
[aggregated in the messages view](base-collectors.html#messages).

## Doctrine

http://doctrine-project.org

Displays sql queries into an SQL queries view using `DebugBar\Bridge\DoctrineCollector`.
You will need to set a `Doctrine\DBAL\Logging\DebugStack` logger on your connection.

    $debugStack = new Doctrine\DBAL\Logging\DebugStack();
    $entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);
    $debugbar->addCollector(new DebugBar\Bridge\DoctrineCollector($debugStack));

`DoctrineCollector` also accepts an `Doctrine\ORM\EntityManager` as argument
provided the `SQLLogger` is a ̀DebugStack`.

## Monolog

https://github.com/Seldaek/monolog

Integrates Monolog messages into a message view using `DebugBar\Bridge\MonologCollector`.

    $logger = new Monolog\Logger('mylogger');
    $debugbar->addCollector(new DebugBar\Bridge\MonologCollector($logger));

Note that multiple logger can be collected:

    $debugbar['monolog']->addLogger($logger);

`MonologCollector` can be [aggregated](base-collectors.html#messages) into the `MessagesCollector`.

## Propel

http://propelorm.org/

Displays propel queries into an SQL queries view using `DebugBar\Bridge\PropelCollector`.
You will need to activate Propel debug mode.

    // before Propel::init()
    $debugbar->addCollector(new DebugBar\Bridge\PropelCollector());

    Propel::init('/path/to/config');

    // after Propel::init()
    // not mandatory if you set config options by yourself
    DebugBar\Bridge\PropelCollector::enablePropelProfiling();

Queries can be collected on a single connection by providing the `PropelPDO` object
to the `PropelCollector` as first argument.

## Slim

http://slimframework.com

Displays message from the Slim logger into a message view using `DebugBar\Bridge\SlimCollector`.

    $app = new Slim\Slim();
    $debugbar->addCollector(new DebugBar\Bridge\SlimCollector($app));

## Swift Mailer

http://swiftmailer.org/

Display log messages and sent mail using `DebugBar\Bridge\SwiftMailer\SwiftLogCollector` and
`DebugBar\Bridge\SwiftMailer\SwiftMailCollector`.

    $mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());
    $debugbar['messages']->aggregate(new DebugBar\Bridge\SwiftMailer\SwiftLogCollector($mailer));
    $debugbar->addCollector(new DebugBar\Bridge\SwiftMailer\SwiftMailCollector($mailer));

## Symfony Mailer

https://symfony.com/doc/current/mailer.html

Display log messages and sent mail using `DebugBar\Bridge\Symfony\SymfonyMailCollector`

    use Symfony\Component\Mailer\Event\SentMessageEvent;

    $mailCollector = new DebugBar\Bridge\Symfony\SymfonyMailCollector();
    $debugbar->addCollector($mailCollector);
    $eventDispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) use (&$mailCollector): void {
        $mailCollector->addSymfonyMessage($event->getMessage());
    });

## Twig

http://twig.sensiolabs.org/

### Version 1 and 2

This collector uses the class `Twig_Extension_Profiler` to collect info about rendered
templates, blocks and macros.
You need to inject the root `Twig_Profiler_Profile` into the collector:

```php
$loader = new Twig_Loader_Filesystem('.');
$env = new Twig_Environment($loader);
$profile = new Twig_Profiler_Profile();
$env->addExtension(new Twig_Extension_Profiler($profile));
$debugbar->addCollector(new DebugBar\Bridge\TwigProfileCollector($profile));
```

### Version 2 and 3

This collector uses the class `Twig\Extension\ProfilerExtension` to collect info about rendered
templates, blocks and macros.
You need to inject the root `Twig\Profiler\Profile` into the collector:

```php
use DebugBar\Bridge\NamespacedTwigProfileCollector;
use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;

$loader = new FilesystemLoader('.');
$env = new Environment($loader);
$profile = new Profile();
$env->addExtension(new ProfilerExtension($profile));
$debugbar->addCollector(new NamespacedTwigProfileCollector($profile));
```

### Optional debugbar twig extensions

You can optionally use `DebugBar\Bridge\Twig\TimeableTwigExtensionProfiler` in place of
`Twig\Profiler\Profile` so render operation can be measured.

```php
use Twig\Environment;use Twig\Loader\FilesystemLoader;use Twig\Profiler\Profile;

$loader = new FilesystemLoader('.');
$env = new Environment($loader);
$profile = new Profile();

$env->addExtension(new demo\TimeableTwigExtensionProfiler($profile, $debugbar['time']));
$debugbar->addCollector(new DebugBar\Bridge\TwigProfileCollector($profile));
```

Other optional extensions add functions and tags for debugbar integration into templates.

```php
use Twig\Environment;use Twig\Loader\FilesystemLoader;use Twig\Profiler\Profile;

$loader = new FilesystemLoader('.');
$env = new Environment($loader);
$profile = new Profile();

// enable {% measure 'foo' %} {% endmeasure %} tags for time measure on templates
// this extension adds timeline items to TimeDataCollector
$twig->addExtension(new demo\MeasureTwigExtension($debugbar['time']));

$twig->enableDebug(); // if Twig\Environment debug is disabled, dump/debug are ignored

// enable {{ dump('foo') }} function on templates
// this extension allows dumping data using debugbar DataFormatter
$twig->addExtension(new demo\DumpTwigExtension());

// enable {{ debug('foo') }} function on templates
// this extension allows debugging in MessageCollector
$twig->addExtension(new demo\DebugTwigExtension($debugbar['messages']));

$debugbar->addCollector(new DebugBar\Bridge\TwigProfileCollector($profile));
```

