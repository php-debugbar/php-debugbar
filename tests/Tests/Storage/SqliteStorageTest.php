<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\Storage\SqliteStorage;

class SqliteStorageTest extends DebugBarTestCase
{
    private string $filepath;
    private SqliteStorage $storage;
    private array $testData;

    public function setUp(): void
    {
        // Create a temporary SQLite database file
        $this->filepath = tempnam(sys_get_temp_dir(), 'debugbar_') . '.sqlite';
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }

        $this->storage = new SqliteStorage($this->filepath);

        $this->testData = [
            '__meta' => [
                'id' => 'test123',
                'utime' => microtime(true),
                'datetime' => date('Y-m-d H:i:s'),
                'uri' => '/test/uri',
                'ip' => '127.0.0.1',
                'method' => 'GET',
            ],
            'messages' => [
                'count' => 1,
                'messages' => ['Hello World'],
            ],
        ];
    }

    public function tearDown(): void
    {
        // Clean up the database file
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
    }

    public function testConstructorCreatesDatabase(): void
    {
        $this->assertFileExists($this->filepath);
    }

    public function testConstructorCreatesDirectoryIfNotExists(): void
    {
        $dir = sys_get_temp_dir() . '/debugbar_test_' . uniqid();
        $filepath = $dir . '/test.sqlite';

        $storage = new SqliteStorage($filepath);

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($filepath);

        // Cleanup
        unlink($filepath);
        rmdir($dir);
    }

    public function testSaveAndGet(): void
    {
        $this->storage->save('foo', $this->testData);

        $retrieved = $this->storage->get('foo');

        $this->assertEquals($this->testData, $retrieved);
    }

    public function testGetNonExistentEntry(): void
    {
        $result = $this->storage->get('nonexistent');

        $this->assertEquals([], $result);
    }

    public function testFind(): void
    {
        // Save multiple entries
        $data1 = $this->testData;
        $data1['__meta']['id'] = 'entry1';
        $data1['__meta']['uri'] = '/page1';

        $data2 = $this->testData;
        $data2['__meta']['id'] = 'entry2';
        $data2['__meta']['uri'] = '/page2';

        $data3 = $this->testData;
        $data3['__meta']['id'] = 'entry3';
        $data3['__meta']['uri'] = '/page1';

        $this->storage->save('entry1', $data1);
        $this->storage->save('entry2', $data2);
        $this->storage->save('entry3', $data3);

        // Find all
        $results = $this->storage->find();
        $this->assertCount(3, $results);

        // Find with filter
        $results = $this->storage->find(['uri' => '/page1']);
        $this->assertCount(2, $results);

        // Test limit
        $results = $this->storage->find([], 2);
        $this->assertCount(2, $results);

        // Test offset
        $results = $this->storage->find([], 20, 1);
        $this->assertCount(2, $results);
    }

    public function testFindOrdersByDatetimeDesc(): void
    {
        // Create entries with different timestamps
        $data1 = $this->testData;
        $data1['__meta']['id'] = 'old';
        $data1['__meta']['utime'] = microtime(true) - 100;
        $data1['__meta']['datetime'] = date('Y-m-d H:i:s', time() - 100);

        $data2 = $this->testData;
        $data2['__meta']['id'] = 'new';
        $data2['__meta']['utime'] = microtime(true);
        $data2['__meta']['datetime'] = date('Y-m-d H:i:s');

        $this->storage->save('old', $data1);
        $this->storage->save('new', $data2);

        $results = $this->storage->find();

        // Most recent should be first
        $this->assertEquals('new', $results[0]['id']);
        $this->assertEquals('old', $results[1]['id']);
    }

    public function testClear(): void
    {
        $this->storage->save('foo', $this->testData);
        $this->storage->save('bar', $this->testData);

        $this->assertEquals(2, $this->storage->count());

        $this->storage->clear();

        $this->assertEquals(0, $this->storage->count());
        $this->assertEquals([], $this->storage->get('foo'));
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->storage->count());

        $this->storage->save('foo', $this->testData);
        $this->assertEquals(1, $this->storage->count());

        $this->storage->save('bar', $this->testData);
        $this->assertEquals(2, $this->storage->count());

        $this->storage->clear();
        $this->assertEquals(0, $this->storage->count());
    }

    public function testGarbageCollectionRemovesOldEntries(): void
    {
        // Create storage with short lifetime
        $storage = new SqliteStorage($this->filepath, 'phpdebugbar');

        // Add old entry
        $oldData = $this->testData;
        $oldData['__meta']['id'] = 'old';
        $oldData['__meta']['utime'] = microtime(true) - 2 * 3600; // 2 hours old
        $storage->save('old', $oldData);

        // Add recent entry
        $newData = $this->testData;
        $newData['__meta']['id'] = 'new';
        $newData['__meta']['utime'] = microtime(true);
        $storage->save('new', $newData);

        $this->assertEquals(2, $storage->count());

        // Run garbage collection (with 1 hour lifetime )
        $storage->prune(1);

        $this->assertEquals(1, $storage->count());

        // Old entry should be deleted
        $this->assertEquals([], $storage->get('old'));

        // New entry should still exist
        $this->assertEquals($newData, $storage->get('new'));
    }

    public function testGarbageCollectionRespectsLimit(): void
    {
        // Create storage with limit of 5 entries
        $storage = new SqliteStorage($this->filepath, 'phpdebugbar');

        // Add 4 fresh entries (under limit)
        for ($i = 1; $i <= 4; $i++) {
            $data = $this->testData;
            $data['__meta']['id'] = "entry$i";
            $data['__meta']['utime'] = microtime(true);
            $storage->save("entry$i", $data);
        }

        $countBefore = $storage->count();

        // GC should not delete anything (all entries are fresh)
        $storage->prune(1); // 1 hour lifetime
        $this->assertEquals($countBefore, $storage->count());
    }

    public function testGarbageCollectionIsCalledOnSave(): void
    {
        // Create storage with very short lifetime and low limit
        $storage = new SqliteStorage($this->filepath, 'phpdebugbar');
        $storage->setAutoPrune(1, 100);

        // Disable automatic GC by saving with force=false first
        // Add old entries that will be expired
        $data1 = $this->testData;
        $data1['__meta']['id'] = 'entry1';
        $data1['__meta']['utime'] = microtime(true) - 2 * 3600; // 10 seconds old

        $data2 = $this->testData;
        $data2['__meta']['id'] = 'entry2';
        $data2['__meta']['utime'] = microtime(true) - 2 * 3600;

        $data3 = $this->testData;
        $data3['__meta']['id'] = 'entry3';
        $data3['__meta']['utime'] = microtime(true) - 2 * 3600;

        // Manually insert to avoid triggering GC during setup
        $pdo = $storage->getPdo();
        $stmt = $pdo->prepare("INSERT INTO phpdebugbar (id, data, meta_utime, meta_datetime, meta_uri, meta_ip, meta_method) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ([$data1, $data2, $data3] as $data) {
            $stmt->execute([
                $data['__meta']['id'],
                json_encode($data),
                $data['__meta']['utime'],
                $data['__meta']['datetime'],
                $data['__meta']['uri'],
                $data['__meta']['ip'],
                $data['__meta']['method'],
            ]);
        }

        $this->assertEquals(3, $storage->count());

        // Add a new entry, which should trigger GC
        $newData = $this->testData;
        $newData['__meta']['id'] = 'new';
        $newData['__meta']['utime'] = microtime(true);
        $storage->save('new', $newData);

        // Old entries should be cleaned up, only new entry should remain
        $count = $storage->count();
        $this->assertEquals(1, $count);

        // Verify the new entry still exists
        $this->assertEquals($newData, $storage->get('new'));
    }

    public function testOptimize(): void
    {
        // Add and remove entries to create fragmentation
        for ($i = 1; $i <= 100; $i++) {
            $data = $this->testData;
            $data['__meta']['id'] = "entry$i";
            $this->storage->save("entry$i", $data);
        }

        $this->storage->clear();

        // VACUUM should not throw an error
        $this->storage->optimize();

        $this->assertEquals(0, $this->storage->count());
    }

    public function testCustomTableName(): void
    {
        $storage = new SqliteStorage($this->filepath, 'custom_table');

        $storage->save('test', $this->testData);

        $pdo = $storage->getPdo();
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_table'");

        $this->assertNotFalse($result->fetch());
    }

    public function testSetGarbageCollectionParams(): void
    {
        $this->storage->setAutoPrune(24);

        // This test just ensures the method doesn't throw an error
        // The actual behavior is tested in other GC tests
        $this->assertEquals(24, $this->storage->getAutoPrune());
    }

    public function testIndexesAreCreated(): void
    {
        $pdo = $this->storage->getPdo();

        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='phpdebugbar'");
        $indexes = $result->fetchAll(\PDO::FETCH_COLUMN);

        $expectedIndexes = [
            'idx_phpdebugbar_utime',
            'idx_phpdebugbar_datetime',
            'idx_phpdebugbar_uri',
            'idx_phpdebugbar_ip',
            'idx_phpdebugbar_method',
        ];

        foreach ($expectedIndexes as $expectedIndex) {
            $this->assertContains($expectedIndex, $indexes);
        }
    }

    public function testHandlesComplexData(): void
    {
        $complexData = [
            '__meta' => [
                'id' => 'complex',
                'utime' => microtime(true),
                'datetime' => date('Y-m-d H:i:s'),
                'uri' => '/test',
                'ip' => '127.0.0.1',
                'method' => 'POST',
            ],
            'nested' => [
                'array' => [1, 2, 3],
                'object' => ['key' => 'value'],
            ],
            'unicode' => 'Hello 世界 🌍',
            'special_chars' => "Line1\nLine2\tTab",
        ];

        $this->storage->save('complex', $complexData);
        $retrieved = $this->storage->get('complex');

        $this->assertEquals($complexData, $retrieved);
    }
}
