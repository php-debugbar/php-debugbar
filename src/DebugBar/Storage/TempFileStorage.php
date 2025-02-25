<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Storage;

/**
 * Stores collected data into files
 */
class TempFileStorage extends FileStorage
{
    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $file = $this->makeFilename($id);
        $data = json_decode(@file_get_contents($file), true);
        @unlink($file);

        return $data;
    }
}
