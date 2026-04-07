<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataFormatter;

use DebugBar\DataFormatter\JsonDataFormatter;
use DebugBar\DataFormatter\VarDumper\DebugBarJsonDumper;
use DebugBar\DataFormatter\VarDumper\ReverseJsonDumper;
use DebugBar\Tests\DebugBarTestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

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
        $data = $d->formatVar([1, 2, 3]);

        // Simple arrays pass through as raw PHP arrays
        $this->assertIsArray($data);
        $this->assertSame([1, 2, 3], $data);
    }

    public function testAssociativeArray(): void
    {
        $d = new JsonDataFormatter();
        $data = $d->formatVar(['foo' => 'bar', 'baz' => 42]);

        // Simple assoc arrays pass through as raw PHP arrays
        $this->assertIsArray($data);
        $this->assertSame(['foo' => 'bar', 'baz' => 42], $data);
    }

    public function testNestedArray(): void
    {
        $d = new JsonDataFormatter();
        $data = $d->formatVar(['foo' => [1, 2], 'bar' => true]);

        // Nested simple arrays pass through as raw PHP arrays
        $this->assertIsArray($data);
        $this->assertSame(['foo' => [1, 2], 'bar' => true], $data);
    }

    public function testObject(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        $data = $d->formatVar($obj);

        // Objects use _vd format with natural key→value
        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data);
        $this->assertEquals(4, $data['_vd'][0]); // HASH_OBJECT
        $this->assertSame('test', $data['name']);
        $this->assertSame(123, $data['value']);
    }

    public function testEmptyArray(): void
    {
        $d = new JsonDataFormatter();
        $data = $d->formatVar([]);

        $this->assertIsArray($data);
        $this->assertSame([], $data);
    }

    public function testDumpAsArray(): void
    {
        $dumper = new DebugBarJsonDumper();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar(['a' => 1]);

        $result = $dumper->dumpAsArray($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('a', $result);
    }

    public function testAssetProvider(): void
    {
        $d = new JsonDataFormatter();
        $assets = $d->getAssets();

        $this->assertArrayHasKey('css', $assets);
        $this->assertEquals('vardumper.css', $assets['css']);
        $this->assertArrayHasKey('js', $assets);
        $this->assertEquals('vardumper.js', $assets['js']);
        $this->assertArrayNotHasKey('inline_head', $assets);
    }

    public function testDumperOptions(): void
    {
        $d = new JsonDataFormatter();
        $options = $d->getDumperOptions();
        $this->assertArrayHasKey('expanded_depth', $options);
        $this->assertEquals(0, $options['expanded_depth']);

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

        $data = $d->formatVar([['one', 'two', 'three', 'four', 'five']]);

        $this->assertIsArray($data);
        // Inner array should be truncated with _cut
        $inner = $data[0];
        $this->assertIsArray($inner);
        $this->assertCount(2, array_filter(array_keys($inner), fn($k) => $k !== '_cut'));
        $this->assertEquals(3, $inner['_cut']);
    }

    public function testStringTruncation(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_string' => 5]);

        $data = $d->formatVar('ABCDEFGHIJ');

        $this->assertIsString($data);
        $this->assertSame('ABCDE[..5]', $data);
    }

    public function testStringWithinLimit(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_string' => 100]);

        $data = $d->formatVar('short string');

        $this->assertIsString($data);
        $this->assertSame('short string', $data);
    }

    public function testStringExactlyAtLimit(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_string' => 10]);

        $data = $d->formatVar('ABCDEFGHIJ');

        $this->assertIsString($data);
        $this->assertSame('ABCDEFGHIJ', $data);
    }

    public function testObjectVisibility(): void
    {
        $d = new JsonDataFormatter();

        $obj = new class {
            public string $pub = 'public_val';
            protected string $prot = 'protected_val';
            /** @phpstan-ignore property.onlyWritten */
            private string $priv = 'private_val';
        };

        $data = $d->formatVar($obj);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data);
        $this->assertEquals(4, $data['_vd'][0]); // HASH_OBJECT

        // Values stored as native key→value
        $this->assertSame('public_val', $data['pub']);
        $this->assertSame('protected_val', $data['prot']);
        $this->assertSame('private_val', $data['priv']);

        // Prefixes in _vd[3]: null=public, *=protected, ClassName=private
        $prefixes = $data['_vd'][3];
        $this->assertNotNull($prefixes);
        $this->assertNull($prefixes[0]);              // pub: public (no prefix)
        $this->assertEquals('*', $prefixes[1]);        // prot: protected
        $this->assertNotNull($prefixes[2]);            // priv: declaring class name
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

        // Simple arrays pass through as raw arrays
        $data = $d->formatVar([1, 2, 3]);
        $this->assertIsArray($data);
        $this->assertSame([1, 2, 3], $data);

        // Objects use _vd format
        $obj = new \stdClass();
        $obj->x = 1;
        $data = $d->formatVar($obj);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data);
    }

    public function testShallowArray(): void
    {
        $d = new JsonDataFormatter();

        // Shallow with plain scalars — still returns as-is (within depth 1)
        $data = $d->formatVar(['a' => [1, 2, 3], 'b' => 'hello'], deep: false);
        $this->assertIsArray($data);
        $this->assertSame('hello', $data['b']);
        $this->assertSame([1, 2, 3], $data['a']);

        // Shallow with deeper nesting — depth 1 cuts nested arrays
        $data = $d->formatVar(['a' => ['b' => ['c' => 'd']]], deep: false);
        $this->assertIsArray($data);
        $this->assertSame(['b' => ['_cut' => 1]], $data['a']);
    }

    public function testArrayWithObjectInlinesDump(): void
    {
        $d = new JsonDataFormatter();

        // Array stays plain, object value gets _vd format inline
        $obj = new \stdClass();
        $obj->x = 1;
        $data = $d->formatVar(['key' => $obj]);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('key', $data);
        // The object value has _vd metadata
        $this->assertArrayHasKey('_vd', $data['key']);
        $this->assertEquals(4, $data['key']['_vd'][0]); // HASH_OBJECT
    }

    /**
     * Tests that formatVar output can be reversed via ReverseJsonDumper::toCloneVarData
     * and dumped back to text, producing the same output as direct CliDumper.
     *
     * @dataProvider reverseRoundtripProvider
     */
    public function testReverseRoundtrip(mixed $value, string $description, bool $deep = true): void
    {
        $formatter = new JsonDataFormatter();
        $cliDumper = new CliDumper();
        $cliDumper->setColors(false);
        $reverse = new ReverseJsonDumper();

        $formatted = $formatter->formatVar($value, $deep);

        // Reverse back to Data and dump as text
        $data = $reverse->toCloneVarData($formatted);
        $actual = rtrim($cliDumper->dump($data, true));

        // Direct dump for comparison
        $cloner = new VarCloner();
        $directData = $cloner->cloneVar($value);
        if (!$deep) {
            $isObj = is_object($value) && !is_iterable($value);
            $directData = $directData->withMaxDepth($isObj ? 0 : 1);
        }
        $expected = rtrim($cliDumper->dump($directData, true));

        $this->assertEquals($expected, $actual, "Reverse roundtrip failed for: $description");
    }

    public static function reverseRoundtripProvider(): iterable
    {
        // Scalars (pass through as-is)
        yield [42, 'integer'];
        yield [3.14, 'float'];
        yield [true, 'boolean true'];
        yield [false, 'boolean false'];
        yield [null, 'null'];
        yield ['hello', 'short string'];

        // Simple flat arrays (plain fast path)
        yield [[], 'empty array'];
        yield [[1, 2, 3], 'indexed array'];
        yield [['foo' => 'bar', 'baz' => 42], 'assoc array'];
        yield [['a' => true, 'b' => null, 'c' => 3.14], 'mixed scalar array'];

        // Nested simple arrays (recursive fast path)
        yield [['x' => [1, 2], 'y' => [3, 4]], 'nested indexed arrays'];
        yield [['a' => ['b' => ['c' => 'd']]], 'deeply nested array'];
        yield [[['one', 'two'], ['three', 'four']], 'array of arrays'];
        yield [['l1' => ['l2' => ['l3' => ['l4' => 'deep']]]], '4 levels deep'];

        // Objects (full dump path)
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        yield [$obj, 'stdClass'];

        // Nested object
        $inner = new \stdClass();
        $inner->x = 1;
        $outer = new \stdClass();
        $outer->child = $inner;
        yield [$outer, 'nested object'];

        // Array with objects (bails to VarCloner)
        $obj = new \stdClass();
        $obj->id = 1;
        yield [['item' => $obj], 'array with object'];
    }

    // ── Additional coverage ──────────────────────────────────────────

    public function testArrayWithObjectBailsToVarCloner(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->x = 1;

        // Object at first position — bails immediately
        $data = $d->formatVar([$obj, 'plain']);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data[0]);
    }

    public function testArrayWithObjectAtEnd(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->x = 1;

        // Object at last position — iterates scalars first, then bails
        $data = $d->formatVar(['a' => 1, 'b' => 'two', 'obj' => $obj]);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data['obj']);
    }

    public function testNestedArrayWithObjectAtLevel2(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->name = 'deep';

        // Object nested 2 levels deep — bail bubbles up
        $data = $d->formatVar(['users' => ['first' => $obj, 'count' => 1]]);
        $this->assertIsArray($data);
        // The entire thing went through VarCloner
        $this->assertArrayHasKey('_vd', $data['users']['first']);
    }

    public function testNestedArrayWithObjectAtLevel3(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->id = 42;

        $data = $d->formatVar(['level1' => ['level2' => ['obj' => $obj, 'n' => 1]]]);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('_vd', $data['level1']['level2']['obj']);
    }

    public function testMixedArrayScalarsAndNestedArrays(): void
    {
        $d = new JsonDataFormatter();

        $data = $d->formatVar([
            'name' => 'test',
            'tags' => ['a', 'b', 'c'],
            'meta' => ['key' => 'value'],
            'count' => 42,
        ]);

        // All plain — no VarCloner needed
        $this->assertSame('test', $data['name']);
        $this->assertSame(['a', 'b', 'c'], $data['tags']);
        $this->assertSame(['key' => 'value'], $data['meta']);
        $this->assertSame(42, $data['count']);
        $this->assertArrayNotHasKey('_vd', $data);
    }

    public function testLargeArrayWithObjectBails(): void
    {
        $d = new JsonDataFormatter();
        $obj = new \stdClass();
        $obj->x = 1;

        // 100 plain items + 1 object at the end
        $input = [];
        for ($i = 0; $i < 100; $i++) {
            $input["k$i"] = "v$i";
        }
        $input['obj'] = $obj;

        $data = $d->formatVar($input);
        $this->assertIsArray($data);
        // Object should still have _vd (entire array went through VarCloner on bail)
        $this->assertArrayHasKey('_vd', $data['obj']);
    }

    public function testMaxDepthCutsNestedArrays(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_depth' => 2]);

        $data = $d->formatVar(['a' => ['b' => ['c' => ['d' => 'deep']]]]);

        // depth 0: outer, depth 1: ['b'=>...], depth 2: ['c'=>...] entered,
        // depth 2 >= maxDepth so ['d'=>'deep'] gets cut
        $this->assertIsArray($data);
        $this->assertSame(['_cut' => 1], $data['a']['b']['c']);
    }

    public function testEmptyNestedArray(): void
    {
        $d = new JsonDataFormatter();

        $data = $d->formatVar(['a' => [], 'b' => [1, []]]);
        $this->assertSame([], $data['a']);
        $this->assertSame([1, []], $data['b']);
    }

    public function testMixedTypesInArray(): void
    {
        $d = new JsonDataFormatter();

        $data = $d->formatVar([null, true, false, 0, 1.5, 'str', [1]]);
        $this->assertSame([null, true, false, 0, 1.5, 'str', [1]], $data);
    }

    public function testStringTruncationInArray(): void
    {
        $d = new JsonDataFormatter();
        $d->resetClonerOptions(['max_string' => 5]);

        $data = $d->formatVar(['short', 'ABCDEFGHIJ']);
        $this->assertSame('short', $data[0]);
        $this->assertSame('ABCDE[..5]', $data[1]);
    }
}
