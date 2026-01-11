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
}
