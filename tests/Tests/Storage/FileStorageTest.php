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

    public function testFindWithUtimeFilter(): void
    {
        // Clear any existing data from setUp
        $this->s->clear();

        // Create entries with different timestamps
        $time1 = microtime(true) - 300; // 5 minutes ago
        $time2 = microtime(true) - 200; // 3.3 minutes ago
        $time3 = microtime(true) - 100; // 1.6 minutes ago

        $data1 = ['__meta' => ['id' => 'entry1', 'utime' => $time1]];
        $data2 = ['__meta' => ['id' => 'entry2', 'utime' => $time2]];
        $data3 = ['__meta' => ['id' => 'entry3', 'utime' => $time3]];

        $this->s->save('entry1', $data1);
        $this->s->save('entry2', $data2);
        $this->s->save('entry3', $data3);

        // Filter for entries AFTER time2 (should return only entry3)
        $results = $this->s->find(['utime' => $time2]);
        $this->assertCount(1, $results);
        $this->assertEquals('entry3', $results[0]['id']);

        // Filter for entries AFTER time1 (should return entry2 and entry3)
        $results = $this->s->find(['utime' => $time1]);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('entry2', $ids);
        $this->assertContains('entry3', $ids);

        // Filter for entries AFTER time3 (should return nothing)
        $results = $this->s->find(['utime' => $time3]);
        $this->assertCount(0, $results);
    }
}
