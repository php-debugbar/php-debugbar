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

        // Simple values are encoded as plain JSON
        $this->assertEquals('42', $out);
    }

    public function testScalarBoolean(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(true);

        $this->assertEquals('true', $out);
    }

    public function testScalarNull(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(null);

        $this->assertEquals('null', $out);
    }

    public function testScalarFloat(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(3.14);

        $this->assertEquals('3.14', $out);
    }

    public function testString(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar('hello world');

        $this->assertEquals('"hello world"', $out);
    }

    public function testSimpleArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar([1, 2, 3]);
        $data = json_decode($out, true);

        // Simple arrays are encoded as plain JSON
        $this->assertEquals([1, 2, 3], $data);
    }

    public function testAssociativeArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(['foo' => 'bar', 'baz' => 42]);
        $data = json_decode($out, true);

        $this->assertEquals(['foo' => 'bar', 'baz' => 42], $data);
    }

    public function testNestedArray(): void
    {
        $d = new JsonDataFormatter();
        $out = $d->formatVar(['foo' => [1, 2], 'bar' => true]);
        $data = json_decode($out, true);

        $this->assertEquals(['foo' => [1, 2], 'bar' => true], $data);
    }

    public function testObject(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        $out = $d->formatVar($obj);
        $data = json_decode($out, true);

        // Objects still use the dump structure
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

        $this->assertEquals('[]', $out);
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

        // With max_items=2, this exceeds the limit so it falls through to Symfony dump
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

        // Long string exceeds max_string, so it falls through to Symfony dump
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

    public function testSimpleValueFastPath(): void
    {
        $d = new JsonDataFormatter();

        // Scalars use plain JSON
        $this->assertEquals('42', $d->formatVar(42));
        $this->assertEquals('true', $d->formatVar(true));
        $this->assertEquals('false', $d->formatVar(false));
        $this->assertEquals('null', $d->formatVar(null));
        $this->assertEquals('"hello"', $d->formatVar('hello'));

        // Simple arrays use plain JSON
        $this->assertEquals('[1,2,3]', $d->formatVar([1, 2, 3]));
        $this->assertEquals('{"foo":"bar"}', $d->formatVar(['foo' => 'bar']));

        // Objects always use dump structure
        $obj = new \stdClass();
        $obj->x = 1;
        $data = json_decode($d->formatVar($obj), true);
        $this->assertEquals('h', $data['t']);
    }

    public function testPrepareVarSimpleValues(): void
    {
        $d = new JsonDataFormatter();

        // Simple values are returned as-is
        $this->assertEquals(42, $d->prepareVar(42));
        $this->assertEquals('hello', $d->prepareVar('hello'));
        $this->assertEquals(['foo' => 'bar'], $d->prepareVar(['foo' => 'bar']));

        // Objects still use dump structure
        $obj = new \stdClass();
        $obj->x = 1;
        $result = $d->prepareVar($obj);
        $this->assertIsArray($result);
        $this->assertEquals('h', $result['t']);
    }

    public function testArrayWithObjectFallsBackToDump(): void
    {
        $d = new JsonDataFormatter();

        // Array containing an object should use Symfony dump
        $obj = new \stdClass();
        $obj->x = 1;
        $out = $d->formatVar(['key' => $obj]);
        $data = json_decode($out, true);

        $this->assertEquals('h', $data['t']);
    }
}
