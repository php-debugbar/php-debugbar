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

    public function testFindWithWildcardFilter(): void
    {
        $this->s->clear();

        $utime = microtime(true);
        $data1 = ['__meta' => ['id' => 'e1', 'utime' => $utime, 'uri' => '/api/users', 'ip' => '127.0.0.1', 'method' => 'GET']];
        $data2 = ['__meta' => ['id' => 'e2', 'utime' => $utime, 'uri' => '/api/posts', 'ip' => '127.0.0.1', 'method' => 'POST']];
        $data3 = ['__meta' => ['id' => 'e3', 'utime' => $utime, 'uri' => '/home', 'ip' => '10.0.0.1', 'method' => 'GET']];

        $this->s->save('e1', $data1);
        $this->s->save('e2', $data2);
        $this->s->save('e3', $data3);

        // Star (*) wildcard matches multiple characters
        $results = $this->s->find(['uri' => '/api/*']);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('e1', $ids);
        $this->assertContains('e2', $ids);

        // Question mark (?) wildcard matches exactly one character
        $results = $this->s->find(['uri' => '/api/user?']);
        $this->assertCount(1, $results);
        $this->assertEquals('e1', $results[0]['id']);

        // Wildcard that matches nothing
        $results = $this->s->find(['uri' => '/other/*']);
        $this->assertCount(0, $results);

        // Wildcard on IP field
        $results = $this->s->find(['ip' => '127.0.*']);
        $this->assertCount(2, $results);

        // Star wildcard matching all entries
        $results = $this->s->find(['uri' => '/*']);
        $this->assertCount(3, $results);
    }
}
