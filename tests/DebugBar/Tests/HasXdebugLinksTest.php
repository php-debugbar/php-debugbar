<?php

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
}
