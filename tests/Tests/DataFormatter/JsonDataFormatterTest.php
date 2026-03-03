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
        $this->assertSame(42, $d->formatVar(42));
    }

    public function testScalarBoolean(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame(true, $d->formatVar(true));
    }

    public function testScalarNull(): void
    {
        $d = new JsonDataFormatter();
        $this->assertNull($d->formatVar(null));
    }

    public function testScalarFloat(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame(3.14, $d->formatVar(3.14));
    }

    public function testString(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame('hello world', $d->formatVar('hello world'));
    }

    public function testSimpleArray(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame([1, 2, 3], $d->formatVar([1, 2, 3]));
    }

    public function testAssociativeArray(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame(['foo' => 'bar', 'baz' => 42], $d->formatVar(['foo' => 'bar', 'baz' => 42]));
    }

    public function testNestedArray(): void
    {
        $d = new JsonDataFormatter();
        $this->assertSame(['foo' => [1, 2], 'bar' => true], $d->formatVar(['foo' => [1, 2], 'bar' => true]));
    }

    public function testObject(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        $data = $d->formatVar($obj);

        // Objects use the dump structure (returned as array)
        $this->assertIsArray($data);
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
        $this->assertSame([], $d->formatVar([]));
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

        // With max_items=2, this exceeds the limit so it falls through to Symfony dump
        $data = $d->formatVar([['one', 'two', 'three', 'four', 'five']]);

        $this->assertIsArray($data);
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

        // Long string exceeds max_string, so it falls through to Symfony dump
        $data = $d->formatVar('ABCDEFGHIJ');

        $this->assertIsArray($data);
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

        $data = $d->formatVar($obj);

        $this->assertIsArray($data);
        $this->assertEquals('h', $data['t']);
        $this->assertEquals(4, $data['ht']); // HASH_OBJECT

        $keyTypes = [];
        foreach ($data['children'] as $child) {
            if (isset($child['kt'])) {
                $keyTypes[$child['k']] = $child['kt'];
            }
        }

        $this->assertEquals('public', $keyTypes['pub']);
        $this->assertEquals('protected', $keyTypes['prot']);
        $this->assertEquals('private', $keyTypes['priv']);
    }

    public function testSimpleValueFastPath(): void
    {
        $d = new JsonDataFormatter();

        // Scalars returned as-is
        $this->assertSame(42, $d->formatVar(42));
        $this->assertSame(true, $d->formatVar(true));
        $this->assertSame(false, $d->formatVar(false));
        $this->assertNull($d->formatVar(null));
        $this->assertSame('hello', $d->formatVar('hello'));

        // Simple arrays returned as-is
        $this->assertSame([1, 2, 3], $d->formatVar([1, 2, 3]));
        $this->assertSame(['foo' => 'bar'], $d->formatVar(['foo' => 'bar']));

        // Objects use dump structure
        $obj = new \stdClass();
        $obj->x = 1;
        $data = $d->formatVar($obj);
        $this->assertIsArray($data);
        $this->assertEquals('h', $data['t']);
    }

    public function testArrayWithObjectFallsBackToDump(): void
    {
        $d = new JsonDataFormatter();

        // Array containing an object should use Symfony dump
        $obj = new \stdClass();
        $obj->x = 1;
        $data = $d->formatVar(['key' => $obj]);

        $this->assertIsArray($data);
        $this->assertEquals('h', $data['t']);
    }
}
