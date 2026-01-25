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

namespace DebugBar\Storage;

/**
 * Stores collected data into files
 */
class FileStorage extends AbstractStorage
{
    protected string $dirname;

    /**
     * @param string $dirname Directories where to store files
     */
    public function __construct(string $dirname)
    {
        $this->dirname = rtrim($dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function save(string $id, array $data): void
    {
        if (!file_exists($this->dirname)) {
            mkdir($this->dirname, 0o755, true);
            file_put_contents($this->dirname . '.gitignore', "*\n!.gitignore\n");
        }
        file_put_contents($this->makeFilename($id), json_encode($data));

        $this->autoPrune();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): array
    {
        $fileName = $this->makeFilename($id);
        if (!file_exists($fileName)) {
            return [];
        }

        $content = file_get_contents($fileName);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file $fileName");
        }

        return json_decode($content, true);
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        //Load the metadata and filter the results.
        $results = [];
        $ids = [];
        $i = 0;

        $files = new \FilesystemIterator($this->dirname, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);
        foreach ($files as $path) {
            if (str_ends_with($path, '.json')) {
                $ids[] = basename($path, '.json');
            }
        }

        // Sort by id
        sort($ids, SORT_STRING);
        $ids = array_reverse($ids);

        $filterUtime = null;
        if ($filters['utime'] ?? false) {
            $filterUtime = (float) $filters['utime'];
            unset($filters['utime']);
        }

        foreach ($ids as $id) {
            //When filter is empty, skip loading the offset
            if ($i++ < $offset && !$filters) {
                $results[] = null;
                continue;
            }

            $data = $this->get($id);
            if (!isset($data['__meta'])) {
                continue;
            }

            $meta = $data['__meta'];
            unset($data);

            if ($filterUtime !== null && $meta['utime'] <= $filterUtime) {
                break;
            }
            if ($this->filter($meta, $filters)) {
                $results[] = $meta;
            }

            if (count($results) >= ($max + $offset)) {
                break;
            }
        }

        return array_slice($results, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     *
     *
     */
    protected function filter(array $meta, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($meta[$key]) || fnmatch($value, $meta[$key]) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        foreach (new \FilesystemIterator($this->dirname, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS) as $path) {
            if (is_file($path) && str_ends_with($path, '.json')) {
                unlink($path);
            }
        }
    }

    public function makeFilename(string $id): string
    {
        return $this->dirname . basename($id) . ".json";
    }

    /**
     * {@inheritdoc}
     */
    public function prune(int $hours = 24): void
    {
        $cutoffTime = time() - $hours * 3600;

        if (!is_dir($this->dirname)) {
            return;
        }

        /** @var \DirectoryIterator $file */
        foreach (new \FilesystemIterator($this->dirname, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.json') && $file->getMTime() < $cutoffTime) {
                unlink($file->getPathname());
            }
        }
    }
}
