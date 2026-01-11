<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\Storage\PdoStorage;

class PdoStorageTest extends DebugBarTestCase
{
    private \PDO $pdo;
    private PdoStorage $s;
    private array $data;

    public function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create the table manually for testing
        $this->pdo->exec('CREATE TABLE phpdebugbar (
            id TEXT PRIMARY KEY,
            data TEXT,
            meta_utime DOUBLE,
            meta_datetime TEXT,
            meta_uri TEXT,
            meta_ip TEXT,
            meta_method TEXT
        )');

        $this->s = new PdoStorage($this->pdo);

        $this->data = ['__meta' => ['id' => 'foo', 'utime' => microtime(true)]];
        $this->s->save('bar', $this->data);
    }

    public function testSave(): void
    {
        $this->s->save('foo', $this->data);
        $data = $this->s->get('foo');
        $this->assertEquals($this->data, $data);
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
        $data = $this->s->get('bar');
        $this->assertEmpty($data);
    }

    public function testPrune(): void
    {
        // Create old and new entries
        $oldData = ['__meta' => ['id' => 'old', 'utime' => microtime(true) - 7200]]; // 2 hours old
        $newData = ['__meta' => ['id' => 'new', 'utime' => microtime(true)]]; // Fresh

        $this->s->save('old', $oldData);
        $this->s->save('new', $newData);

        // Verify both entries exist
        $this->assertNotEmpty($this->s->get('old'));
        $this->assertNotEmpty($this->s->get('new'));

        // Delete entries older than 1 hour
        $this->s->prune(1);

        $this->assertEmpty($this->s->get('old'));
        $this->assertNotEmpty($this->s->get('new'));
    }

    public function testGarbageCollectionWithDefaultLifetime(): void
    {
        // Create a very old entry (2 days)
        $veryOldData = ['__meta' => ['id' => 'veryold', 'utime' => microtime(true) - 172800]];
        $this->s->setAutoPrune(false);
        $this->s->save('veryold', $veryOldData);

        // Verify entry exists
        $this->assertNotEmpty($this->s->get('veryold'));

        // Use default lifetime (1 day = 86400 seconds)
        $this->s->prune();

        $this->assertEmpty($this->s->get('veryold'));
    }

    public function testGarbageCollectionNoOldEntries(): void
    {
        // All entries are fresh
        $this->s->prune(1);

        $this->assertNotEmpty($this->s->get('bar'));
    }

    public function testGarbageCollectionMultipleOldEntries(): void
    {
        // Create multiple old entries
        for ($i = 0; $i < 5; $i++) {
            $oldData = ['__meta' => ['id' => "old$i", 'utime' => microtime(true) - 7200]];
            $this->s->save("old$i", $oldData);
        }

        // Verify all entries exist
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotEmpty($this->s->get("old$i"));
        }

        // Delete all old entries
        $this->s->prune(1);

        // Verify all entries are gone
        for ($i = 0; $i < 5; $i++) {
            $this->assertEmpty($this->s->get("old$i"));
        }
    }
}
