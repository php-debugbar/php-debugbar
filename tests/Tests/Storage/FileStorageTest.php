<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\Storage\FileStorage;

class FileStorageTest extends DebugBarTestCase
{
    private string $dirname;
    private FileStorage $s;
    private array $data;

    public function setUp(): void
    {
        $this->dirname = tempnam(sys_get_temp_dir(), 'debugbar');
        if (file_exists($this->dirname)) {
            unlink($this->dirname);
        }
        mkdir($this->dirname, 0o777);
        $this->s = new FileStorage($this->dirname);
        $this->data = ['__meta' => ['id' => 'foo']];
        $this->s->save('bar', $this->data);
    }

    public function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dirname, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($this->dirname);
    }

    public function testSave(): void
    {
        $this->s->save('foo', $this->data);
        $this->assertFileExists($this->dirname . '/foo.json');
        $this->assertJsonStringEqualsJsonFile($this->dirname . '/foo.json', json_encode($this->data));
    }

    public function testGet(): void
    {
        $data = $this->s->get('bar');
        $this->assertEquals($this->data, $data);
    }

    public function testFind(): void
    {
        $results = $this->s->find();
        $this->assertContains($this->data['__meta'], $results);
    }

    public function testClear(): void
    {
        $this->s->clear();

        $this->assertFileDoesNotExist($this->dirname . '/foo.json');
    }

    public function testPrune(): void
    {
        // Create some test files with different ages
        $oldData = ['__meta' => ['id' => 'old', 'utime' => microtime(true) - 7200]]; // 2 hours old
        $newData = ['__meta' => ['id' => 'new', 'utime' => microtime(true)]]; // Fresh

        $this->s->save('old', $oldData);
        $this->s->save('new', $newData);

        // Touch the old file to make it actually old (2 hours)
        touch($this->dirname . '/old.json', time() - 7200);

        // Delete entries older than 1 hour (3600 seconds)
        $this->s->prune(1);

        $this->assertFileDoesNotExist($this->dirname . '/old.json');
        $this->assertFileExists($this->dirname . '/new.json');
    }

    public function testGarbageCollectionWithDefaultLifetime(): void
    {
        // Create an old file (2 days old)
        $oldData = ['__meta' => ['id' => 'veryold', 'utime' => microtime(true) - 172800]];
        $this->s->save('veryold', $oldData);
        touch($this->dirname . '/veryold.json', time() - 172800);

        // Use default lifetime (1 day)
        $this->s->prune();

        $this->assertFileDoesNotExist($this->dirname . '/veryold.json');
    }

    public function testGarbageCollectionNoOldEntries(): void
    {
        // All files are fresh
        $this->s->prune(1);

        $this->assertFileExists($this->dirname . '/bar.json');
    }
}
