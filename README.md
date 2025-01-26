# PHP Debug Bar

[![Latest Stable Version](https://img.shields.io/packagist/v/maximebf/debugbar?label=Stable)](https://packagist.org/packages/maximebf/debugbar) [![Total Downloads](https://img.shields.io/packagist/dt/maximebf/debugbar?label=Downloads)](https://packagist.org/packages/maximebf/debugbar) [![License](https://img.shields.io/badge/Licence-MIT-4d9283)](https://packagist.org/packages/maximebf/debugbar) [![Tests](https://github.com/maximebf/php-debugbar/actions/workflows/run-tests.yml/badge.svg)](https://github.com/maximebf/php-debugbar/actions/workflows/run-tests.yml)

Displays a debug bar in the browser with information from php.
No more `var_dump()` in your code!

> **Note: Debug Bar is for development use only. Never install this on websites that are publicly accessible.**

![Screenshot](https://raw.github.com/maximebf/php-debugbar/master/docs/screenshot.png)

**Features:**

 - Generic debug bar
 - Easy to integrate with any project
 - Clean, fast and easy to use interface
 - Handles AJAX request
 - Includes generic data collectors and collectors for well known libraries
 - The client side bar is 100% coded in javascript
 - Easily create your own collectors and their associated view in the bar
 - Save and re-open previous requests
 - [Very well documented](http://phpdebugbar.com/docs)

Includes collectors for:

  - [PDO](http://php.net/manual/en/book.pdo.php)
  - [CacheCache](http://maximebf.github.io/CacheCache/)
  - [Doctrine](http://doctrine-project.org)
  - [Monolog](https://github.com/Seldaek/monolog)
  - [Propel](http://propelorm.org/)
  - [Slim](http://slimframework.com)
  - [Symfony Mailer](https://symfony.com/doc/current/mailer.html)
  - [Swift Mailer](http://swiftmailer.org/)
  - [Twig](http://twig.symfony.com/)

Checkout the [demo](https://github.com/maximebf/php-debugbar/tree/master/demo) for
examples and [phpdebugbar.com](http://phpdebugbar.com) for a live example.

Integrations with other frameworks:

  - [Laravel](https://github.com/barryvdh/laravel-debugbar)
  - [Atomik](http://atomikframework.com/docs/error-log-debug.html#debug-bar)
  - [XOOPS](http://xoops.org/modules/news/article.php?storyid=6538)
  - [Zend Framework 2](https://github.com/snapshotpl/ZfSnapPhpDebugBar)
  - [Phalcon](https://github.com/snowair/phalcon-debugbar)
  - [SilverStripe](https://github.com/lekoala/silverstripe-debugbar)
  - [Grav CMS](https://getgrav.org)
  - [TYPO3](https://github.com/Konafets/typo3_debugbar)
  - [Joomla](https://github.com/joomla/joomla-cms/blob/4.0-dev/plugins/system/debug/debug.php)
  - [Drupal](https://www.drupal.org/project/debugbar)
  - [October CMS](https://github.com/rainlab/debugbar-plugin)
  - Framework-agnostic middleware and PSR-7 with [php-middleware/phpdebugbar](https://github.com/php-middleware/phpdebugbar)
  - [Dotkernel Frontend Application](https://github.com/dotkernel/dot-debugbar)

*(drop me a message or submit a PR to add your DebugBar related project here)*

## Installation

The best way to install DebugBar is using [Composer](http://getcomposer.org)
with the following command:

```bash
composer require maximebf/debugbar --dev
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

Learn more about DebugBar in the [docs](http://phpdebugbar.com/docs).


## Demo

To run the demo, clone this repository and start the Built-In PHP webserver from the root:

```
php -S localhost:8000
```

Then visit http://localhost:8000/demo/

## Testing

To test, run `php vendor/bin/phpunit`. 
To debug Browser tests, you can run `PANTHER_NO_HEADLESS=1 vendor/bin/phpunit --debug`. Run `vendor/bin/bdi detect drivers` to download the latest drivers.
