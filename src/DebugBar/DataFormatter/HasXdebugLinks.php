<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\DataCollector;

trait HasXdebugLinks
{
    protected $xdebugLinkTemplate = '';
    protected $xdebugShouldUseAjax = false;
    protected $xdebugReplacements = array();
    protected $wslName = null;
    /**
     * Set the WSL distribution name for vscode-wsl links.
     *
     * @param string|null $wslName
     */
    public function setWslName($wslName)
    {
        $this->wslName = $wslName;
    }

    /**
     * Get the WSL distribution name, from property, env, or default.
     *
     * @return string
     */
    public function getWslName()
    {
        if ($this->wslName) {
            return $this->wslName;
        }
        $env = getenv('DEBUGBAR_WSL_NAME');
        if ($env) {
            return $env;
        }
        // Optionally, set a default here, e.g. 'Ubuntu'
        return 'Ubuntu';
    }

    /**
     * Shorten the file path by removing the xdebug path replacements
     *
     * @param string $file
     * @return string
     */
    public function normalizeFilePath($file)
    {
        if (empty($file)) {
            return '';
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        foreach (array_keys($this->xdebugReplacements) as $path) {
            if (strpos($file, $path) === 0) {
                $file = substr($file, strlen($path));
                break;
            }
        }

        return ltrim(str_replace('\\', '/', $file), '/');
    }

    /**
     * Get an Xdebug Link to a file
     *
     * @param string $file
     * @param int|null $line
     *
     * @return array {
     * @var string   $url
     * @var bool     $ajax should be used to open the url instead of a normal links
     * }
     */
    public function getXdebugLink($file, $line = null)
    {
        if (empty($file)) {
            return null;
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        foreach ($this->xdebugReplacements as $path => $replacement) {
            if (strpos($file, $path) === 0) {
                $file = $replacement . substr($file, strlen($path));
                break;
            }
        }

        $template = $this->getXdebugLinkTemplate();
        $replacements = [
            '%f' => rawurlencode(str_replace('\\', '/', $file)),
            '%l' => rawurlencode((string) $line ?: 1),
        ];
        // Add %w for WSL name if needed
        if (strpos($template, '%w') !== false) {
            $replacements['%w'] = rawurlencode($this->getWslName());
        }
        $url = strtr($template, $replacements);
        if ($url) {
            return [
                'url' => $url,
                'ajax' => $this->getXdebugShouldUseAjax(),
                'filename' => basename($file),
                'line' => (string) $line ?: '?'
            ];
        }
    }

    /**
     * @return string
     */
    public function getXdebugLinkTemplate()
    {
        if (empty($this->xdebugLinkTemplate) && !empty(ini_get('xdebug.file_link_format'))) {
            $this->xdebugLinkTemplate = ini_get('xdebug.file_link_format');
        }

        return $this->xdebugLinkTemplate;
    }

    /**
     * @param string $editor
     */
    public function setEditorLinkTemplate($editor)
    {
        $editorLinkTemplates = array(
            'sublime' => 'subl://open?url=file://%f&line=%l',
            'textmate' => 'txmt://open?url=file://%f&line=%l',
            'emacs' => 'emacs://open?url=file://%f&line=%l',
            'macvim' => 'mvim://open/?url=file://%f&line=%l',
            'codelite' => 'codelite://open?file=%f&line=%l',
            'phpstorm' => 'phpstorm://open?file=%f&line=%l',
            'phpstorm-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;' .
                'r.open(\'get\',\'http://localhost:63342/api/file/%f:%l\');r.send();})()',
            'idea' => 'idea://open?file=%f&line=%l',
            'idea-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;' .
                'r.open(\'get\',\'http://localhost:63342/api/file/?file=%f&line=%l\');r.send();})()',
            'vscode' => 'vscode://file/%f:%l',
            'vscode-insiders' => 'vscode-insiders://file/%f:%l',
            'vscode-remote' => 'vscode://vscode-remote/%f:%l',
            'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%f:%l',
            'vscode-wsl' => 'vscode://vscode-remote/wsl+%w/%f:%l',
            'vscodium' => 'vscodium://file/%f:%l',
            'nova' => 'nova://open?path=%f&line=%l',
            'xdebug' => 'xdebug://%f@%l',
            'atom' => 'atom://core/open/file?filename=%f&line=%l',
            'espresso' => 'x-espresso://open?filepath=%f&lines=%l',
            'netbeans' => 'netbeans://open/?f=%f:%l',
            'cursor' => 'cursor://file/%f:%l',
            'windsurf' => 'windsurf://file/%f:%l',
            'zed' => 'zed://file/%f:%l',
            'antigravity' => 'antigravity://file/%f:%l',
        );

        if (is_string($editor) && isset($editorLinkTemplates[$editor])) {
            $this->setXdebugLinkTemplate($editorLinkTemplates[$editor]);
        }
    }

    /**
     * @param string $xdebugLinkTemplate
     * @param bool $shouldUseAjax
     */
    public function setXdebugLinkTemplate($xdebugLinkTemplate, $shouldUseAjax = false)
    {
        if ($xdebugLinkTemplate === 'idea') {
            $this->xdebugLinkTemplate = 'http://localhost:63342/api/file/?file=%f&line=%l';
            $this->xdebugShouldUseAjax = true;
        } else {
            $this->xdebugLinkTemplate = $xdebugLinkTemplate;
            $this->xdebugShouldUseAjax = $shouldUseAjax;
        }
    }

    /**
     * @return bool
     */
    public function getXdebugShouldUseAjax()
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
    public function getXdebugReplacements()
    {
        return $this->xdebugReplacements;
    }

    /**
     * @param array $xdebugReplacements
     */
    public function addXdebugReplacements($xdebugReplacements)
    {
        foreach ($xdebugReplacements as $serverPath => $replacement) {
            $this->setXdebugReplacement($serverPath, $replacement);
        }
    }

    /**
     * @param array $xdebugReplacements
     */
    public function setXdebugReplacements($xdebugReplacements)
    {
        $this->xdebugReplacements = $xdebugReplacements;
    }

    /**
     * @param string $serverPath
     * @param string $replacement
     */
    public function setXdebugReplacement($serverPath, $replacement)
    {
        $this->xdebugReplacements[$serverPath] = $replacement;
    }
}
