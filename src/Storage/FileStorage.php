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
        $i = 0;

        /** @var \SplFileInfo $file */
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if (!str_ends_with($file->getFilename(), '.json')) {
                continue;
            }

            //When filter is empty, skip loading the offset
            if ($i++ < $offset && !$filters) {
                $results[] = null;
                continue;
            }

            $id = $file->getBasename('.json');
            $data = $this->get($id);
            if (!isset($data['__meta'])) {
                continue;
            }

            $meta = $data['__meta'];
            unset($data);
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
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if (substr($file->getFilename(), 0, 1) !== '.') {
                unlink($file->getPathname());
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
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if (substr($file->getFilename(), 0, 1) !== '.' && $file->getMTime() < $cutoffTime) {
                unlink($file->getPathname());
            }
        }
    }
}
