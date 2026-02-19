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

    public function testGlobToSql(): void
    {
        // Basic wildcard conversion
        $this->assertEquals('/api/%', $this->s->globToSql('/api/*'));
        $this->assertEquals('/page_', $this->s->globToSql('/page?'));
        $this->assertEquals('%', $this->s->globToSql('*'));

        // Literal SQL wildcards in the pattern must be escaped
        $this->assertEquals('/100\%/test', $this->s->globToSql('/100%/test'));
        $this->assertEquals('/col\_name', $this->s->globToSql('/col_name'));

        // Combined: glob wildcards alongside literal SQL special chars
        $this->assertEquals('/100\%/%', $this->s->globToSql('/100%/*'));
        $this->assertEquals('/col\_%', $this->s->globToSql('/col_*'));
    }

    public function testFindWithUriContainingPercent(): void
    {
        $this->s->clear();

        $t = microtime(true);
        $this->s->save('r1', ['__meta' => ['id' => 'r1', 'utime' => $t, 'uri' => '/api/100%20/users']]);
        $this->s->save('r2', ['__meta' => ['id' => 'r2', 'utime' => $t, 'uri' => '/api/100%20/posts']]);
        $this->s->save('r3', ['__meta' => ['id' => 'r3', 'utime' => $t, 'uri' => '/api/other']]);

        // Literal % in the filter should not act as a SQL wildcard
        $results = $this->s->find(['uri' => '/api/100%20/*']);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('r1', $ids);
        $this->assertContains('r2', $ids);
        $this->assertNotContains('r3', $ids);

        // Exact match with % in the URI
        $results = $this->s->find(['uri' => '/api/100%20/users']);
        $this->assertCount(1, $results);
        $this->assertEquals('r1', $results[0]['id']);
    }

    public function testFindWithUriContainingUnderscore(): void
    {
        $this->s->clear();

        $t = microtime(true);
        $this->s->save('r1', ['__meta' => ['id' => 'r1', 'utime' => $t, 'uri' => '/api_v1/users']]);
        $this->s->save('r2', ['__meta' => ['id' => 'r2', 'utime' => $t, 'uri' => '/api_v2/users']]);
        $this->s->save('r3', ['__meta' => ['id' => 'r3', 'utime' => $t, 'uri' => '/apixv1/users']]);

        // Literal _ in the filter should not act as a SQL single-char wildcard
        $results = $this->s->find(['uri' => '/api_v1/*']);
        $this->assertCount(1, $results);
        $this->assertEquals('r1', $results[0]['id']);

        // ? glob wildcard does match the _ character
        $results = $this->s->find(['uri' => '/api?v1/*']);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('r1', $ids);
        $this->assertContains('r3', $ids);
    }

    public function testFindWithIpWildcard(): void
    {
        $this->s->clear();

        $t = microtime(true);
        $this->s->save('r1', ['__meta' => ['id' => 'r1', 'utime' => $t, 'ip' => '192.168.1.10']]);
        $this->s->save('r2', ['__meta' => ['id' => 'r2', 'utime' => $t, 'ip' => '192.168.2.20']]);
        $this->s->save('r3', ['__meta' => ['id' => 'r3', 'utime' => $t, 'ip' => '10.0.0.1']]);

        // * matches any sequence of characters
        $results = $this->s->find(['ip' => '192.168.*']);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('r1', $ids);
        $this->assertContains('r2', $ids);
        $this->assertNotContains('r3', $ids);

        // ? matches exactly one character
        $results = $this->s->find(['ip' => '10.0.0.?']);
        $this->assertCount(1, $results);
        $this->assertEquals('r3', $results[0]['id']);

        // no wildcards still works as exact match
        $results = $this->s->find(['ip' => '192.168.1.10']);
        $this->assertCount(1, $results);
        $this->assertEquals('r1', $results[0]['id']);
    }

    public function testFindWithUriWildcard(): void
    {
        $this->s->clear();

        $t = microtime(true);
        $this->s->save('r1', ['__meta' => ['id' => 'r1', 'utime' => $t, 'uri' => '/api/users']]);
        $this->s->save('r2', ['__meta' => ['id' => 'r2', 'utime' => $t, 'uri' => '/api/posts']]);
        $this->s->save('r3', ['__meta' => ['id' => 'r3', 'utime' => $t, 'uri' => '/admin']]);

        // * wildcard matches all /api/* URIs
        $results = $this->s->find(['uri' => '/api/*']);
        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('r1', $ids);
        $this->assertContains('r2', $ids);
        $this->assertNotContains('r3', $ids);

        // ? wildcard matches exactly one character at that position
        $results = $this->s->find(['uri' => '/admi?']);
        $this->assertCount(1, $results);
        $this->assertEquals('r3', $results[0]['id']);

        // no match
        $results = $this->s->find(['uri' => '/other/*']);
        $this->assertCount(0, $results);
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
