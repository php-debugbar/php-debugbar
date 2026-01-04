# Bridge collectors

DebugBar comes with some "bridge" collectors. These collectors provides a way to integrate other projects with the DebugBar.

## Symfony HttpFoundation Request / Response

https://symfony.com/doc/current/components/http_foundation.html

This extends the [existing RequestDataCollector](base.md#requestdata) for the Symfony HttpFoundation Request and Response.

```php
use Symfony\Component\Mailer\Event\SentMessageEvent;

$debugbar->addCollector(new DebugBar\Bridge\Symfony\SymfonyRequestCollector($request, $response));
```

### Inject the debugbar into the Symfony HttpFoundation Response

To inject the debugbar into the Symfony HttpFoundation Response, you can use the JavascriptRenderer:

```php
// Inject Debugbar
$debugbar->getJavascriptRenderer()->injectInSymfonyResponse($response);
```

This will call renderHead() and render() just before the closing </body> tag. You can disable the header by setting false as second parameter.

## Doctrine

http://doctrine-project.org

> Requires https://github.com/php-debugbar/doctrine-bridge

Displays sql queries into an SQL queries view using `DebugBar\Bridge\DoctrineCollector`.
You will need to set a `Doctrine\DBAL\Logging\DebugStack` logger on your connection.

```php
$debugStack = new Doctrine\DBAL\Logging\DebugStack();
$entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);
$debugbar->addCollector(new DebugBar\Bridge\Doctrine\DoctrineCollector($debugStack));
```
`DoctrineCollector` also accepts an `Doctrine\ORM\EntityManager` as argument
provided the `SQLLogger` is a ̀DebugStack`.

## Monolog

https://github.com/Seldaek/monolog

Integrates Monolog messages into a message view using `DebugBar\Bridge\MonologCollector`.

```php
$logger = new Monolog\Logger('mylogger');
$debugbar->addCollector(new DebugBar\Bridge\MonologCollector($logger));
```
Note that multiple logger can be collected:

```php
$debugbar['monolog']->addLogger($logger);
```
`MonologCollector` can be [aggregated](base.md#messages) into the `MessagesCollector`.


## Symfony Mailer

https://symfony.com/doc/current/mailer.html

Display log messages and sent mail using `DebugBar\Bridge\Symfony\SymfonyMailCollector`

```php
use Symfony\Component\Mailer\Event\SentMessageEvent;

$mailCollector = new DebugBar\Bridge\Symfony\SymfonyMailCollector();
$debugbar->addCollector($mailCollector);
$eventDispatcher->addListener(SentMessageEvent::class, function (SentMessageEvent $event) use (&$mailCollector): void {
    $mailCollector->addSymfonyMessage($event->getMessage());
});
```

## Twig

http://twig.sensiolabs.org/

> Requires https://github.com/php-debugbar/twig-bridge


This collector uses the class `Twig\Extension\ProfilerExtension` to collect info about rendered
templates, blocks and macros.
You need to inject the root `Twig\Profiler\Profile` into the collector:

```php
use DebugBar\Bridge\Twig\NamespacedTwigProfileCollector;
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
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;

$loader = new FilesystemLoader('.');
$env = new Environment($loader);
$profile = new Profile();

$env->addExtension(new DebugBar\Bridge\Twig\TimeableTwigExtensionProfiler($profile, $debugbar['time']));
$debugbar->addCollector(new DebugBar\Bridge\Twig\TwigProfileCollector($profile));
```

Other optional extensions add functions and tags for debugbar integration into templates.

```php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;

$loader = new FilesystemLoader('.');
$env = new Environment($loader);
$profile = new Profile();

// enable {% measure 'foo' %} {% endmeasure %} tags for time measure on templates
// this extension adds timeline items to TimeDataCollector
$twig->addExtension(new DebugBar\Bridge\Twig\MeasureTwigExtension($debugbar['time']));

$twig->enableDebug(); // if Twig\Environment debug is disabled, dump/debug are ignored

// enable {{ dump('foo') }} function on templates
// this extension allows dumping data using debugbar DataFormatter
$twig->addExtension(new DebugBar\Bridge\Twig\DumpTwigExtension());

// enable {{ debug('foo') }} function on templates
// this extension allows debugging in MessageCollector
$twig->addExtension(new DebugBar\Bridge\Twig\DebugTwigExtension($debugbar['messages']));

$debugbar->addCollector(new DebugBar\Bridge\TwigProfileCollector($profile));
```
