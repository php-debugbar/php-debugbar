<?php

declare(strict_types=1);

/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataFormatter;

trait HasXdebugLinks
{
    protected string $xdebugLinkTemplate = '';
    protected bool $xdebugShouldUseAjax = false;
    protected array $xdebugReplacements = [];

    /**
     * Shorten the file path by removing the xdebug path replacements
     *
     *
     */
    public function normalizeFilePath(string $file): string
    {
        if ($file === '') {
            return '';
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        if ($file === false) {
            return '';
        }

        foreach (array_keys($this->xdebugReplacements) as $path) {
            if (str_starts_with($file, $path)) {
                $file = substr($file, strlen($path));
                break;
            }
        }

        return ltrim(str_replace('\\', '/', $file), '/');
    }

    /**
     * Get an Xdebug Link to a file
     *
     *
     * @return null|array{url: string, ajax: bool, filename: string, line: string}
     */
    public function getXdebugLink(string $file, ?int $line = null): ?array
    {
        if ($file === '') {
            return null;
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        if ($file === false) {
            return null;
        }

        foreach ($this->xdebugReplacements as $path => $replacement) {
            if (str_starts_with($file, $path)) {
                $file = $replacement . substr($file, strlen($path));
                break;
            }
        }

        $url = strtr($this->getXdebugLinkTemplate(), [
            '%f' => rawurlencode(str_replace('\\', '/', $file)),
            '%l' => rawurlencode((string) $line ?: "1"),
        ]);

        if (!$url) {
            return null;
        }

        return [
            'url' => $url,
            'ajax' => $this->getXdebugShouldUseAjax(),
            'filename' => basename($file),
            'line' => (string) $line ?: '?',
        ];
    }

    public function getXdebugLinkTemplate(): string
    {
        if (!$this->xdebugLinkTemplate && ini_get('xdebug.file_link_format')) {
            $this->xdebugLinkTemplate = ini_get('xdebug.file_link_format');
        }

        return $this->xdebugLinkTemplate;
    }

    public function setEditorLinkTemplate(string $editor): void
    {
        $editorLinkTemplates = [
            'sublime' => 'subl://open?url=file://%f&line=%l',
            'textmate' => 'txmt://open?url=file://%f&line=%l',
            'emacs' => 'emacs://open?url=file://%f&line=%l',
            'macvim' => 'mvim://open/?url=file://%f&line=%l',
            'codelite' => 'codelite://open?file=%f&line=%l',
            'phpstorm' => 'phpstorm://open?file=%f&line=%l',
            'phpstorm-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;'
                . 'r.open(\'get\',\'http://localhost:63342/api/file/%f:%l\');r.send();})()',
            'idea' => 'idea://open?file=%f&line=%l',
            'idea-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;'
                . 'r.open(\'get\',\'http://localhost:63342/api/file/?file=%f&line=%l\');r.send();})()',
            'vscode' => 'vscode://file/%f:%l',
            'vscode-insiders' => 'vscode-insiders://file/%f:%l',
            'vscode-remote' => 'vscode://vscode-remote/%f:%l',
            'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%f:%l',
            'vscodium' => 'vscodium://file/%f:%l',
            'nova' => 'nova://open?path=%f&line=%l',
            'xdebug' => 'xdebug://%f@%l',
            'atom' => 'atom://core/open/file?filename=%f&line=%l',
            'espresso' => 'x-espresso://open?filepath=%f&lines=%l',
            'netbeans' => 'netbeans://open/?f=%f:%l',
            'cursor' => 'cursor://file/%f:%l',
            'cursor-remote' => 'cursor://vscode-remote/%f:%l',
            'windsurf' => 'windsurf://file/%f:%l',
            'zed' => 'zed://file/%f:%l',
            'antigravity' => 'antigravity://file/%f:%l',
        ];

        if (isset($editorLinkTemplates[$editor])) {
            $this->setXdebugLinkTemplate($editorLinkTemplates[$editor]);
        }
    }

    public function setXdebugLinkTemplate(string $xdebugLinkTemplate, bool $shouldUseAjax = false): void
    {
        if ($xdebugLinkTemplate === 'idea') {
            $this->xdebugLinkTemplate = 'http://localhost:63342/api/file/?file=%f&line=%l';
            $this->xdebugShouldUseAjax = true;
        } else {
            $this->xdebugLinkTemplate = $xdebugLinkTemplate;
            $this->xdebugShouldUseAjax = $shouldUseAjax;
        }
    }

    public function getXdebugShouldUseAjax(): bool
    {
        return $this->xdebugShouldUseAjax;
    }

    /**
     * returns an array of filename-replacements
     *
     * this is useful f.e. when using vagrant or remote servers,
     * where the path of the file is different between server and
     * development environment
     *
     * @return array key-value-pairs of replacements, key = path on server, value = replacement
     */
    public function getXdebugReplacements(): array
    {
        return $this->xdebugReplacements;
    }

    public function addXdebugReplacements(array $xdebugReplacements): void
    {
        foreach ($xdebugReplacements as $serverPath => $replacement) {
            $this->setXdebugReplacement($serverPath, $replacement);
        }
    }

    public function setXdebugReplacements(array $xdebugReplacements): void
    {
        $this->xdebugReplacements = $xdebugReplacements;
    }

    public function setXdebugReplacement(string $serverPath, string $replacement): void
    {
        $this->xdebugReplacements[$serverPath] = $replacement;
    }
}
