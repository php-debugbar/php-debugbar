# PHP Debug Bar

[![Latest Stable Version](https://img.shields.io/packagist/v/php-debugbar/php-debugbar?label=Stable)](https://packagist.org/packages/php-debugbar/php-debugbar) [![Total Downloads](https://img.shields.io/packagist/dt/maximebf/debugbar?label=Downloads)](https://packagist.org/packages/php-debugbar/php-debugbar) [![License](https://img.shields.io/badge/Licence-MIT-4d9283)](https://packagist.org/packages/php-debugbar/php-debugbar) [![Tests](https://github.com/php-debugbar/php-debugbar/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/php-debugbar/php-debugbar/actions/workflows/run-tests.yml)

Displays a debug bar in the browser with information from php.
No more `var_dump()` in your code!

> **Note: Debug Bar is for development use only. Never install this on websites that are publicly accessible.**

> Debugbar has had significant updates in 3.x (January 2026).
> See the [Release Notes](https://php-debugbar.com/docs/release-notes/) for more information and breaking changes.

![Screenshot](https://raw.github.com/php-debugbar/php-debugbar/master/docs/screenshot.png)

**Features:**

 - Generic Debug Bar for PHP projects
 - Easy to integrate with any project
 - Clean, fast and easy to use interface
 - Handles AJAX request
 - Includes generic data collectors and collectors for well known libraries
 - The client side bar is 100% coded in plain javascript
 - Easily create your own collectors and their associated view in the bar
 - Save and re-open previous requests
 - [Very well documented](http://php-debugbar.com/docs/)

Includes collectors for:
  - Messages
  - Config
  - Time
  - Memory
  - Exceptions
  - PHP Info
  - Request Data
  - Templates
  - Object Count
  - [PDO](http://php.net/manual/en/book.pdo.php)
  - [Monolog](https://github.com/Seldaek/monolog)
  - [Symfony Mailer](https://symfony.com/doc/current/mailer.html)
  - [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html)

Checkout the [demo](https://github.com/php-debugbar/php-debugbar/tree/master/demo) for
examples and [php-debugbar.com](http://php-debugbar.com) for a live example.

Additional collectors are available here:
 - [Twig](https://github.com/php-debugbar/twig-bridge)
 - [Doctrine](https://github.com/php-debugbar/doctrine-bridge)
 - [Monolog](https://github.com/php-debugbar/monolog-bridge)
 - [Symfony](https://github.com/php-debugbar/symfony-bridge)

Integrations with other frameworks:

  - [Laravel](https://github.com/barryvdh/laravel-debugbar)
  - [Zend Framework 2](https://github.com/snapshotpl/ZfSnapPhpDebugBar)
  - [Phalcon](https://github.com/snowair/phalcon-debugbar)
  - [SilverStripe](https://github.com/lekoala/silverstripe-debugbar)
  - [Grav CMS](https://getgrav.org)
  - [TYPO3](https://github.com/Konafets/typo3_debugbar)
  - [Joomla](https://github.com/joomla/joomla-cms/blob/4.0-dev/plugins/system/debug/debug.php)
  - [Drupal](https://www.drupal.org/project/debugbar)
  - [October CMS](https://github.com/rainlab/debugbar-plugin)
[Winter CMS](https://packagist.org/packages/winter/wn-debugbar-plugin)
  - Framework-agnostic middleware and PSR-7 with [php-middleware/phpdebugbar](https://github.com/php-middleware/phpdebugbar)
  - [Dotkernel Frontend Application](https://github.com/dotkernel/dot-debugbar)

*(drop me a message or submit a PR to add your DebugBar related project here)*

## Installation

The best way to install DebugBar is using [Composer](http://getcomposer.org)
with the following command:

```bash
composer require --dev php-debugbar/php-debugbar
```

## Quick start

DebugBar is very easy to use and you can add it to any of your projects in no time.
The easiest way is using the `render()` functions

```PHP
<?php

// Require the Composer autoloader, if not already loaded
require 'vendor/autoload.php';

use DebugBar\StandardDebugBar;

$debugbar = new StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer();

$debugbar["messages"]->addMessage("hello world!");
?>
<html>
    <head>
        <?php echo $debugbarRenderer->renderHead() ?>
    </head>
    <body>
        ...
        <?php echo $debugbarRenderer->render() ?>
    </body>
</html>
```

The DebugBar uses DataCollectors to collect data from your PHP code. Some of them are
automated but others are manual. Use the `DebugBar` like an array where keys are the
collector names. In our previous example, we add a message to the `MessagesCollector`:

```PHP
$debugbar["messages"]->addMessage("hello world!");
```

`StandardDebugBar` activates the following collectors:

 - `MemoryCollector` (*memory*)
 - `MessagesCollector` (*messages*)
 - `PhpInfoCollector` (*php*)
 - `RequestDataCollector` (*request*)
 - `TimeDataCollector` (*time*)
 - `ExceptionsCollector` (*exceptions*)

Learn more about DebugBar in the [docs](http://php-debugbar.com/docs/).

## Demo

To run the demo, clone this repository and start the Built-In PHP webserver from the demo folder:

```
composer run demo
```

Then visit http://localhost:8000/

## Testing

To test, run `php vendor/bin/phpunit`. 
To debug Browser tests, you can run `PANTHER_NO_HEADLESS=1 vendor/bin/phpunit --debug`. Run `vendor/bin/bdi detect drivers` to download the latest drivers.

## Contributing
When contributing to the JavaScript codebase:

1. Run `npm run lint` and `npm run build` before committing 
2. Fix any errors (warnings are acceptable but should be minimized)
3. Use `npm run lint:fix` for automatic fixes where possible
4. Follow the ES6+ patterns established in the codebase

When contributing to the PHP codebase:

1. Run `composer check-style` and `composer analyse` before committing.
2. Make sure the tests pass (see above)
3. Verify that the demo works correctly (`php -S localhost:8000 demo/`)
