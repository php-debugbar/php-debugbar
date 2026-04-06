<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class ReverseJsonDumper
{
    public function toCloneVarData(mixed $data): Data
    {
        $result = $this->wrapJsonDumps($data);

        $cloner = new VarCloner();
        $cloner->addCasters(DebugBarJsonCaster::getCasters());

        return $cloner->cloneVar($result);
    }

    private function wrapJsonDumps(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // New format: _vd metadata → wrap as DebugBarJsonVar
        if (array_key_exists('_vd', $data)) {
            return new DebugBarJsonVar($data);
        }

        // Legacy format: _sd marker → wrap as DebugBarJsonVar
        if (array_key_exists('_sd', $data)) {
            return new DebugBarJsonVar($data);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->wrapJsonDumps($value);
        }

        return $data;
    }

    public function reverseFormatVar(mixed $node): string
    {
        return $this->valueToText($node, 0);
    }

    // ---------------------------------------------------------------
    // Value → Text reconstruction (mirrors CliDumper output format)
    // ---------------------------------------------------------------
    private function valueToText(mixed $value, int $depth): string
    {
        if ($value === null) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_int($value)) return (string) $value;
        if (is_float($value)) {
            $s = (string) $value;
            return !str_contains($s, '.') ? $s . '.0' : $s;
        }
        if (is_string($value)) {
            if (str_contains($value, "\n")) {
                $display = str_replace("\n", '\n' . "\n", $value);
                return '"""' . "\n" . $display . "\n" . '"""';
            }
            return '"' . $value . '"';
        }
        if (is_array($value)) {
            // New _vd format: object/resource
            if (isset($value['_vd'])) {
                return $this->vdHashToText($value, $depth);
            }
            // Legacy format
            if (isset($value['t'])) {
                return $this->jsonToText($value, $depth);
            }
            // Plain array
            return $this->plainArrayToText($value, $depth);
        }
        return '';
    }

    private function plainArrayToText(array $data, int $depth): string
    {
        $cut = $data['_cut'] ?? 0;
        $keys = array_keys(array_diff_key($data, array_flip(['_cut'])));
        $count = count($keys) + $cut;
        $isIndexed = array_is_list($data) || ($keys === [] && $cut > 0);

        if ($count === 0) return '[]';

        $header = $count > 0 ? 'array:' . $count . ' [' : '[';
        if ($keys === [] && $cut > 0) {
            return $header . ' …' . $cut . ']';
        }

        $indent = str_repeat('  ', $depth + 1);
        $lines = [];
        foreach ($keys as $i => $key) {
            $line = $indent;
            if ($isIndexed) {
                $line .= $key . ' => ';
            } else {
                $line .= '"' . $key . '" => ';
            }
            $line .= $this->valueToText($data[$key], $depth + 1);
            $lines[] = $line;
        }
        if ($cut > 0) {
            $lines[] = $indent . '…' . $cut;
        }
        $closingIndent = str_repeat('  ', $depth);
        return $header . "\n" . implode("\n", $lines) . "\n" . $closingIndent . ']';
    }

    private function vdHashToText(array $node, int $depth): string
    {
        $vd = $node['_vd'];
        $ht = $vd[0];
        $ref = $vd[1] ?? 0;
        $cls = $vd[2] ?? null;
        $prefixes = $vd[3] ?? null;
        $cut = $node['_cut'] ?? 0;
        $keys = array_keys(array_diff_key($node, array_flip(['_vd', '_cut', '_sd'])));

        $isObject = ($ht === Cursor::HASH_OBJECT);
        $isResource = ($ht === Cursor::HASH_RESOURCE);

        // Header
        if ($isObject) {
            $header = ($cls ? $cls . ' ' : '') . '{';
            if ($ref) {
                $header .= '#' . $ref;
            }
        } elseif ($isResource) {
            $header = ($cls ? $cls . ' ' : '') . '{';
        } else {
            $header = '[';
        }
        $closingChar = ($isObject || $isResource) ? '}' : ']';

        if ($keys === [] && $cut === 0) {
            return $header . $closingChar;
        }
        if ($keys === [] && $cut > 0) {
            return $header . ' …' . $cut . $closingChar;
        }

        $indent = str_repeat('  ', $depth + 1);
        $lines = [];
        foreach ($keys as $i => $key) {
            $line = $indent;
            $prefix = $prefixes[$i] ?? null;
            $line .= match ($prefix) {
                null => '+' . $key . ': ',
                '+' => '+"' . $key . '": ',
                '~' => $key . ': ',
                '*' => '#' . $key . ': ',
                default => '-' . $key . ': ',
            };
            $line .= $this->valueToText($node[$key], $depth + 1);
            $lines[] = $line;
        }
        if ($cut > 0) {
            $lines[] = $indent . '…' . $cut;
        }
        $closingIndent = str_repeat('  ', $depth);
        return $header . "\n" . implode("\n", $lines) . "\n" . $closingIndent . $closingChar;
    }

    // Legacy format support
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
        return match ($node['s']) {
            'i' => (string) $node['v'],
            'd' => !str_contains($s = (string) $node['v'], '.') ? $s . '.0' : $s,
            'b' => $node['v'] ? 'true' : 'false',
            'n' => 'null',
            'l' => $node['v'] ?? '',
            default => (string) ($node['v'] ?? ''),
        };
    }

    private function stringToText(array $node): string
    {
        $v = $node['v'];

        // Binary strings get a 'b' prefix
        $prefix = ($node['bin'] ?? false) ? 'b' : '';

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
        $children = $node['c'] ?? [];
        $cls = $node['cls'] ?? null;
        $cut = $node['cut'] ?? 0;
        $ref = $node['ref'] ?? null;

        $lines = [];

        // Header
        if ($isObject) {
            $header = ($cls && $cls !== 'stdClass') ? ($cls . ' ') : '';
            $header .= '{';
            if ($ref) {
                $header .= '#' . (is_array($ref) ? $ref['s'] : $ref);
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
        if ($children === [] && $cut === 0) {
            return $header . $closingChar;
        }

        // Compact cut-only (no children to expand)
        if ($children === [] && $cut > 0) {
            return $header . ' …' . $cut . $closingChar;
        }

        $indent = str_repeat('  ', $depth + 1);

        // Children
        foreach ($children as $i => $entry) {
            $line = $indent;
            $line .= $this->entryKeyToText($entry, $ht, $i);

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

    private function entryKeyToText(array $entry, int $ht, int $index): string
    {
        $k = $entry['k'] ?? $index;

        // New compact format: single prefix for object/resource visibility
        if (isset($entry['p'])) {
            return match ($entry['p']) {
                '+' => '+"' . $k . '": ',
                '~' => $k . ': ',
                '*' => '#' . $k . ': ',
                '' => '-' . $k . ': ',
                default => '-' . $k . ': ',  // private with declaring class
            };
        }

        // Legacy/inferred kt format
        $kt = $entry['kt'] ?? null;
        if ($kt === null) {
            if (isset($entry['k']) || $ht === Cursor::HASH_INDEXED) {
                if ($ht === Cursor::HASH_INDEXED) {
                    $kt = 'i';
                } elseif ($ht === Cursor::HASH_RESOURCE) {
                    $kt = 'meta';
                } elseif ($ht === Cursor::HASH_OBJECT) {
                    $kt = 'pub';
                } else {
                    $kt = is_int($entry['k']) ? 'i' : 'k';
                }
            } else {
                return '';
            }
        }

        $isDynamic = ($entry['dyn'] ?? false) === true;

        return match ($kt) {
            'i' => $k . ' => ',
            'k' => is_int($k) ? ($k . ' => ') : ('"' . $k . '" => '),
            'pub' => $isDynamic ? '+"' . $k . '": ' : '+' . $k . ': ',
            'pro' => '#' . $k . ': ',
            'pri' => '-' . $k . ': ',
            'meta' => $k . ': ',
            default => $k . ': ',
        };
    }

}
