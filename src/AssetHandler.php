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

namespace DebugBar;

/**
 * Handler to render assets
 */
class AssetHandler
{
    public function __construct(protected readonly DebugBar $debugBar) {}

    /**
     * Handles the current request
     *
     * @param null|array{type: 'css'|'js'} $request
     *
     * @throws DebugBarException
     */
    public function handle(?array $request = null, bool $echo = true, bool $sendHeader = true): string
    {
        if ($request === null) {
            $request = $_GET;
        }

        $renderer = $this->debugBar->getJavascriptRenderer();

        $type = $request['type'] ?? null;

        if (!is_string($type)) {
            throw new DebugBarException("Missing type parameter in request");
        } elseif ($type === 'css') {
            $content = $renderer->dumpAssets(files: $renderer->getAssets()['css'], echo: false);
            $contentType = 'text/css';
        } elseif ($type === 'js') {
            $content = $renderer->dumpAssets(files: $renderer->getAssets()['js'], echo: false);
            $contentType = 'text/javascript';
        } else {
            throw new DebugBarException("Invalid type '{$type}'");
        }

        if ($sendHeader) {
            $this->debugBar->getHttpDriver()->setHeaders([
                'Content-Type' => $contentType,
                'Cache-Control' => 'public,max-age=86400',
                'ETag' => md5($content),
            ]);
        }

        if ($echo) {
            $this->debugBar->getHttpDriver()->output($content);
        }

        return $content;
    }
}
