<?php

namespace DebugBar\Tests;

use PHPUnit\Framework\TestCase;
use DebugBar\DataFormatter\HasXdebugLinks;

class HasXdebugLinksTest extends TestCase
{
    use HasXdebugLinks;

    public function testVscodeWslLinkGeneration()
    {
        $this->setEditorLinkTemplate('vscode-wsl');
        $this->setWslName('CustomWSL');
        $file = '/var/www/html/index.php';
        $line = 42;
        $link = $this->getXdebugLink($file, $line);
        $this->assertStringContainsString('vscode://vscode-remote/wsl+CustomWSL', $link['url']);
        $this->assertStringContainsString('index.php:42', urldecode($link['url']));
    }

    public function testVscodeWslLinkGenerationWithDefaultWslName()
    {
        $this->setEditorLinkTemplate('vscode-wsl');
        // Do not set WSL name, should default to 'Ubuntu'
        $file = '/var/www/html/test.php';
        $line = 5;
        $link = $this->getXdebugLink($file, $line);
        $this->assertStringContainsString('vscode://vscode-remote/wsl+Ubuntu', $link['url']);
        $this->assertStringContainsString('test.php:5', urldecode($link['url']));
    }

    public function testVscodeWslLinkGenerationWithEnvVar()
    {
        $this->setEditorLinkTemplate('vscode-wsl');
        putenv('DEBUGBAR_WSL_NAME=EnvWSL');
        // Do not set WSL name via setter, should use env var
        $file = '/var/www/html/env.php';
        $line = 7;
        $link = $this->getXdebugLink($file, $line);
        $this->assertStringContainsString('vscode://vscode-remote/wsl+EnvWSL', $link['url']);
        $this->assertStringContainsString('env.php:7', urldecode($link['url']));
        putenv('DEBUGBAR_WSL_NAME'); // Clean up
    }
}
