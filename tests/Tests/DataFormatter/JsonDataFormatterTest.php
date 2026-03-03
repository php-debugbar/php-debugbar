<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataFormatter;

use DebugBar\DataFormatter\JsonDataFormatter;
use DebugBar\DataFormatter\VarDumper\DebugBarJsonDumper;
use DebugBar\Tests\DebugBarTestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class JsonDataFormatterTest extends DebugBarTestCase
{
    public function testScalarInteger(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(42);
        $data = json_decode($out, true);

        $this->assertEquals('s', $data['t']);
        $this->assertEquals('integer', $data['st']);
        $this->assertEquals(42, $data['v']);
    }

    public function testScalarBoolean(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(true);
        $data = json_decode($out, true);

        $this->assertEquals('s', $data['t']);
        $this->assertEquals('boolean', $data['st']);
        $this->assertTrue($data['v']);
    }

    public function testScalarNull(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(null);
        $data = json_decode($out, true);

        $this->assertEquals('s', $data['t']);
        $this->assertEquals('NULL', $data['st']);
        $this->assertNull($data['v']);
    }

    public function testScalarFloat(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(3.14);
        $data = json_decode($out, true);

        $this->assertEquals('s', $data['t']);
        $this->assertEquals('double', $data['st']);
        $this->assertEquals(3.14, $data['v']);
    }

    public function testString(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar('hello world');
        $data = json_decode($out, true);

        $this->assertEquals('r', $data['t']);
        $this->assertEquals('hello world', $data['v']);
    }

    public function testSimpleArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar([1, 2, 3]);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertEquals(2, $data['ht']); // HASH_INDEXED
        $this->assertEquals(3, $data['cls']);
        $this->assertCount(3, $data['children']);

        // Check first child
        $child = $data['children'][0];
        $this->assertEquals(0, $child['k']);
        $this->assertEquals('index', $child['kt']);
        $this->assertEquals('s', $child['n']['t']);
        $this->assertEquals(1, $child['n']['v']);
    }

    public function testAssociativeArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(['foo' => 'bar', 'baz' => 42]);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertEquals(1, $data['ht']); // HASH_ASSOC
        $this->assertCount(2, $data['children']);

        $this->assertEquals('foo', $data['children'][0]['k']);
        $this->assertEquals('key', $data['children'][0]['kt']);
        $this->assertEquals('r', $data['children'][0]['n']['t']);
        $this->assertEquals('bar', $data['children'][0]['n']['v']);

        $this->assertEquals('baz', $data['children'][1]['k']);
        $this->assertEquals('key', $data['children'][1]['kt']);
        $this->assertEquals('s', $data['children'][1]['n']['t']);
        $this->assertEquals(42, $data['children'][1]['n']['v']);
    }

    public function testNestedArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(['foo' => [1, 2], 'bar' => true]);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertCount(2, $data['children']);

        // Nested array
        $nested = $data['children'][0]['n'];
        $this->assertEquals('h', $nested['t']);
        $this->assertEquals(2, $nested['ht']); // HASH_INDEXED
        $this->assertCount(2, $nested['children']);

        // Boolean
        $bool = $data['children'][1]['n'];
        $this->assertEquals('s', $bool['t']);
        $this->assertEquals('boolean', $bool['st']);
        $this->assertTrue($bool['v']);
    }

    public function testObject(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        $out = $d->formatVar($obj);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertEquals(4, $data['ht']); // HASH_OBJECT
        $this->assertEquals('stdClass', $data['cls']);
        $this->assertCount(2, $data['children']);

        // Properties should be public
        $this->assertEquals('name', $data['children'][0]['k']);
        $this->assertEquals('public', $data['children'][0]['kt']);
    }

    public function testEmptyArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar([]);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertEquals(0, $data['cls']);
        $this->assertEmpty($data['children']);
    }

    public function testValidJson(): void
    {
        $d = new JsonDataFormatter();
        $testData = [
            'string' => 'hello',
            'int' => 42,
            'nested' => ['a', 'b', 'c'],
        ];
        $out = $d->formatVar($testData);

        // Must be valid JSON
        $this->assertNotNull(json_decode($out));
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    public function testDumpAsArray(): void
    {
        $dumper = new DebugBarJsonDumper();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar(['a' => 1]);

        $result = $dumper->dumpAsArray($data);
        $this->assertIsArray($result);
        $this->assertEquals('h', $result['t']);
    }

    public function testAssetProvider(): void
    {
        $d = new JsonDataFormatter();
        $assets = $d->getAssets();

        $this->assertArrayHasKey('base_path', $assets);
        $this->assertArrayHasKey('css', $assets);
        $this->assertArrayHasKey('js', $assets);
        $this->assertEquals('widget.css', $assets['css']);
        $this->assertEquals('widget.js', $assets['js']);
    }

    public function testDumperOptions(): void
    {
        $d = new JsonDataFormatter();
        $options = $d->getDumperOptions();
        $this->assertArrayHasKey('expanded_depth', $options);
        $this->assertEquals(1, $options['expanded_depth']);

        $d->mergeDumperOptions(['expanded_depth' => 3]);
        $this->assertEquals(3, $d->getDumperOptions()['expanded_depth']);

        $d->resetDumperOptions(['expanded_depth' => 2]);
        $this->assertEquals(2, $d->getDumperOptions()['expanded_depth']);
    }

    public function testClonerOptions(): void
    {
        $d = new JsonDataFormatter();
        $options = $d->getClonerOptions();
        $this->assertEquals(['max_string' => 10000, 'max_items' => 1000], $options);
    }

    public function testMaxItems(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_items' => 2]);

        $out = $d->formatVar([['one', 'two', 'three', 'four', 'five']]);
        $data = json_decode($out, true);

        // The inner array should be cut
        $inner = $data['children'][0]['n'];
        $this->assertEquals('h', $inner['t']);
        $this->assertCount(2, $inner['children']);
        $this->assertGreaterThan(0, $inner['cut']);
    }

    public function testStringTruncation(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_string' => 5]);

        $out = $d->formatVar('ABCDEFGHIJ');
        $data = json_decode($out, true);

        $this->assertEquals('r', $data['t']);
        $this->assertEquals('ABCDE', $data['v']);
        $this->assertEquals(5, $data['cut']);
        $this->assertEquals(10, $data['len']);
    }

    public function testObjectVisibility(): void
    {
        $d = new JsonDataFormatter();

        $obj = new class {
            public string $pub = 'public_val';
            protected string $prot = 'protected_val';
            private string $priv = 'private_val';
        };

        $out = $d->formatVar($obj);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
        $this->assertEquals(4, $data['ht']); // HASH_OBJECT

        $keyTypes = [];
        foreach ($data['children'] as $child) {
            // Skip meta entries (like class info)
            if (isset($child['kt'])) {
                $keyTypes[$child['k']] = $child['kt'];
            }
        }

        $this->assertEquals('public', $keyTypes['pub']);
        $this->assertEquals('protected', $keyTypes['prot']);
        $this->assertEquals('private', $keyTypes['priv']);
    }
}
