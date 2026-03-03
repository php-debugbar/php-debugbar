<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataFormatter;

use DebugBar\DataFormatter\VarDumper\DebugBarJsonDumper;
use DebugBar\Tests\DebugBarTestCase;
use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Proves the JSON dump structure is lossless by roundtripping:
 *   PHP value → VarCloner → DebugBarJsonDumper → JSON array → text reconstruction
 * and comparing with:
 *   PHP value → VarCloner → CliDumper → text
 *
 * If they match, the JSON contains all the information needed to reconstruct
 * the exact same output (whether as text, HTML, or DOM elements).
 */
class JsonRoundtripTest extends DebugBarTestCase
{
    private VarCloner $cloner;
    private CliDumper $cliDumper;
    private DebugBarJsonDumper $jsonDumper;

    public function setUp(): void
    {
        $this->cloner = new VarCloner();
        $this->cliDumper = new CliDumper();
        $this->cliDumper->setColors(false);
        $this->jsonDumper = new DebugBarJsonDumper();
    }

    /**
     * @dataProvider valueProvider
     */
    public function testRoundtrip(mixed $value, string $description, ?int $maxDepth = null, ?int $maxItems = null, ?int $maxString = null): void
    {
        if ($maxItems !== null) {
            $this->cloner->setMaxItems($maxItems);
        }
        if ($maxString !== null) {
            $this->cloner->setMaxString($maxString);
        }

        $data = $this->cloner->cloneVar($value);
        if ($maxDepth !== null) {
            $data = $data->withMaxDepth($maxDepth);
        }

        // Expected: CliDumper text output
        $expected = rtrim($this->cliDumper->dump($data, true));

        // Actual: JSON → text reconstruction
        $json = $this->jsonDumper->dumpAsArray($data);
        $actual = rtrim($this->jsonToText($json, 0));

        $this->assertEquals($expected, $actual, "Roundtrip failed for: $description");
    }

    public static function valueProvider(): iterable
    {
        // Scalars
        yield [42, 'integer'];
        yield [0, 'zero'];
        yield [-1, 'negative integer'];
        yield [3.14, 'float'];
        yield [0.0, 'zero float'];
        yield [true, 'boolean true'];
        yield [false, 'boolean false'];
        yield [null, 'null'];

        // Strings
        yield ['hello world', 'simple string'];
        yield ['', 'empty string'];
        yield ['a', 'single char'];
        yield ["line1\nline2", 'multiline string'];

        // Arrays
        yield [[], 'empty array'];
        yield [[1, 2, 3], 'indexed array'];
        yield [['foo' => 'bar', 'baz' => 42], 'assoc array'];
        yield [['a' => [1, 2], 'b' => true], 'nested array'];
        yield [[0 => 'a', 2 => 'b'], 'sparse indexed array'];

        // Objects
        yield [new \stdClass(), 'empty stdClass'];
        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 123;
        yield [$obj, 'stdClass with properties'];

        // Typed properties
        yield [new class {
            public string $pub = 'a';
            protected string $prot = 'b';
            private string $priv = 'c';
        }, 'visibility'];

        // Nested object
        $inner = new \stdClass();
        $inner->x = 1;
        $outer = new \stdClass();
        $outer->child = $inner;
        yield [$outer, 'nested object'];

        // Duplicate reference
        $shared = new \stdClass();
        $shared->x = 1;
        yield [['first' => $shared, 'second' => $shared], 'duplicate object ref'];

        // Mixed array with objects
        yield [['key' => 'value', 'obj' => new \stdClass()], 'mixed array'];

        // Depth cut
        yield [['a' => ['b' => ['c' => 'd']]], 'depth cut', 2];

        // Max items
        yield [['one', 'two', 'three', 'four', 'five'], 'max items', null, 3];

        // String truncation
        yield ['ABCDEFGHIJKLMNOP', 'string truncation', null, null, 5];

        // Hard references
        $x = 42;
        yield [['x' => &$x, 'y' => &$x], 'hard reference'];
    }

    // ---------------------------------------------------------------
    // JSON → Text reconstruction (mirrors CliDumper output format)
    // ---------------------------------------------------------------

    private function jsonToText(array $node, int $depth): string
    {
        return match ($node['t'] ?? null) {
            's' => $this->scalarToText($node),
            'r' => $this->stringToText($node),
            'h' => $this->hashToText($node, $depth),
            default => '',
        };
    }

    private function scalarToText(array $node): string
    {
        return match ($node['st']) {
            'integer' => (string) $node['v'],
            'double' => !str_contains($s = (string) $node['v'], '.') ? $s . '.0' : $s,
            'boolean' => $node['v'] ? 'true' : 'false',
            'NULL' => 'null',
            'label' => $node['v'] ?? '',
            default => (string) ($node['v'] ?? ''),
        };
    }

    private function stringToText(array $node): string
    {
        $v = $node['v'];

        // Binary strings get a 'b' prefix
        $prefix = !empty($node['bin']) ? 'b' : '';

        if (isset($node['cut']) && $node['cut'] > 0) {
            return $prefix . '"' . $v . '"…' . $node['cut'];
        }

        // Multiline strings use triple-quote format in CliDumper
        // Real newlines in the string are shown as literal \n followed by a real newline
        if (str_contains($v, "\n")) {
            $display = str_replace("\n", '\n' . "\n", $v);
            return $prefix . '"""' . "\n" . $display . "\n" . '"""';
        }

        return $prefix . '"' . $v . '"';
    }

    private function hashToText(array $node, int $depth): string
    {
        $ht = $node['ht'];
        $isObject = ($ht === Cursor::HASH_OBJECT);
        $isResource = ($ht === Cursor::HASH_RESOURCE);
        $isArray = ($ht === Cursor::HASH_ASSOC || $ht === Cursor::HASH_INDEXED);
        $children = $node['children'] ?? [];
        $cls = $node['cls'] ?? null;
        $cut = $node['cut'] ?? 0;
        $ref = $node['ref'] ?? null;

        $lines = [];

        // Header
        if ($isObject) {
            $header = ($cls && $cls !== 'stdClass') ? ($cls . ' ') : '';
            $header .= '{';
            if ($ref) {
                $header .= '#' . $ref['s'];
            }
        } elseif ($isResource) {
            $header = ($cls ? $cls . ' ' : '') . '{';
        } else {
            // Array
            if ($cls) {
                $header = 'array:' . $cls . ' [';
            } else {
                $header = '[';
            }
        }

        $closingChar = $isArray ? ']' : '}';

        // Empty hash
        if (empty($children) && $cut === 0) {
            return $header . $closingChar;
        }

        // Compact cut-only (no children to expand)
        if (empty($children) && $cut > 0) {
            return $header . ' …' . $cut . $closingChar;
        }

        $indent = str_repeat('  ', $depth + 1);

        // Children
        foreach ($children as $entry) {
            $line = $indent;
            $line .= $this->entryKeyToText($entry);

            // Hard reference
            if (isset($entry['ref'])) {
                $line .= '&' . $entry['ref'] . ' ';
            }

            // Value
            $line .= $this->jsonToText($entry['n'], $depth + 1);
            $lines[] = $line;
        }

        // Cut indicator
        if ($cut > 0) {
            $lines[] = $indent . '…' . $cut;
        }

        $closingIndent = str_repeat('  ', $depth);

        return $header . "\n" . implode("\n", $lines) . "\n" . $closingIndent . $closingChar;
    }

    private function entryKeyToText(array $entry): string
    {
        if (!isset($entry['kt'])) {
            return '';
        }

        $k = $entry['k'];
        $kt = $entry['kt'];
        $isDynamic = !empty($entry['dyn']);

        return match ($kt) {
            'index' => $k . ' => ',
            'key' => is_int($k) ? ($k . ' => ') : ('"' . $k . '" => '),
            'public' => $isDynamic ? '+"' . $k . '": ' : '+' . $k . ': ',
            'protected' => '#' . $k . ': ',
            'private' => '-' . $k . ': ',
            'meta' => $k . ': ',
            default => $k . ': ',
        };
    }
}
