<?php

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Cloner\Cursor;

class ReverseJsonDumper
{
    public function reverseFormatVar(array $node): string
    {
        return $this->jsonToText($node, 0);
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
        // Infer missing kt from parent hash type
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

        // Infer missing k from loop index (HASH_INDEXED)
        $k = $entry['k'] ?? $index;
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
