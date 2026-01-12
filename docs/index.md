---
title: PHP Debug Bar
description: Displays a debug bar in the browser with information from php.
hide:
  - navigation
---

# PHP Debug Bar

[![Latest Stable Version](https://img.shields.io/packagist/v/php-debugbar/php-debugbar?label=Stable)](https://packagist.org/packages/maximebf/debugbar) [![Total Downloads](https://img.shields.io/packagist/dt/maximebf/debugbar?label=Downloads)](https://packagist.org/packages/php-debugbar/php-debugbar) [![License](https://img.shields.io/badge/Licence-MIT-4d9283)](https://packagist.org/packages/maximebf/debugbar) [![Tests](https://github.com/php-debugbar/php-debugbar/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/maximebf/php-debugbar/actions/workflows/run-tests.yml)

Displays a debug bar in the browser with information from php.
No more `var_dump()` in your code!

> **Note: Debug Bar is for development use only. Never install this on websites that are publicly accessible.**

## V3 Beta
Currently v3 is in beta. You can install it with `composer require php-debugbar/php-debugbar:^3.0@beta`.
See the [Release Notes](docs/release-notes.md) for more information and breaking changes.


<img src="screenshot.png" alt="Screenshot" style="max-width: 774px"/>

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
- [Winter CMS](https://packagist.org/packages/winter/wn-debugbar-plugin)
- Framework-agnostic middleware and PSR-7 with [php-middleware/phpdebugbar](https://github.com/php-middleware/phpdebugbar)
- [Dotkernel Frontend Application](https://github.com/dotkernel/dot-debugbar)

*(drop me a message or submit a PR to add your DebugBar related project here)*

