<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\DumperInterface;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * Dumps variables as JSON-serializable structures using a natural tree format.
 *
 * Values are stored as native JSON types. Only objects/resources carry metadata
 * in a `_vd` sidecar key: [ht, ref?, class?, prefixes?]
 *
 * Format:
 *  - Scalars: native JSON (null, bool, int, float)
 *  - Strings: native JSON string, truncated with "[..N]" suffix
 *  - Arrays:  native JSON arrays/objects (handled by JsonDataFormatter)
 *  - Objects: {"_vd": [ht, ref?, class?, prefixes?], key: value, ...}
 *  - Resources: {"_vd": [5, 0, class, prefixes?], key: value, ...}
 *  - Cut collections: "_cut" key with remaining count
 *
 * Legacy format (with "t", "ht", "c", "_sd" keys) is still supported by the JS renderer.
 */
class DebugBarJsonDumper implements DumperInterface, DataDumperInterface
{
    /** @var array Stack of [parentResult, parentKeys, pendingCursor] */
    private array $stack = [];

    /** @var mixed The root result after dumping */
    private mixed $root = null;

    /** @var array|null Current hash result being populated (the key→value object) */
    private ?array $currentResult = null;

    /** @var list<string> Keys in insertion order for current hash */
    private array $currentKeys = [];

    /** @var Cursor|null Cursor state for the current item */
    private ?Cursor $pendingCursor = null;

    /** @var int Current hash type */
    private int $currentHt = 0;

    /**
     * Dump a Data object and return the JSON string.
     */
    public function dump(Data $data): ?string
    {
        $array = $this->dumpAsArray($data);
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Dump a Data object and return the raw PHP value (avoids double-encoding).
     */
    public function dumpAsArray(Data $data): mixed
    {
        $this->stack = [];
        $this->root = null;
        $this->currentResult = null;
        $this->currentKeys = [];
        $this->pendingCursor = null;
        $this->currentHt = 0;

        $data->dump($this);

        return $this->root;
    }

    public function dumpScalar(Cursor $cursor, string $type, $value): void
    {
        // Scalars map directly to native JSON types
        $native = match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'double' => (float) $value,
            'NULL' => null,
            'label' => (string) ($value ?? ''),
            default => $value,
        };

        $this->emitValue($cursor, $native);
    }

    public function dumpString(Cursor $cursor, string $str, bool $bin, int $cut): void
    {
        if ($cut > 0) {
            $totalLen = ($bin ? strlen($str) : mb_strlen($str, 'UTF-8')) + $cut;
            $str .= '[..' . ($totalLen - ($bin ? strlen($str) : mb_strlen($str, 'UTF-8'))) . ']';
        }

        $this->emitValue($cursor, $str);
    }

    public function enterHash(Cursor $cursor, int $type, $class, bool $hasChild): void
    {
        // Push current context onto stack
        if ($this->currentResult !== null) {
            $this->stack[] = [$this->currentResult, $this->currentKeys, $this->pendingCursor, $this->currentHt];
        }

        $this->currentResult = [];
        $this->currentKeys = [];
        $this->currentHt = $type;
        $this->pendingCursor = clone $cursor;
    }

    public function leaveHash(Cursor $cursor, int $type, $class, bool $hasChild, int $cut): void
    {
        $result = $this->currentResult;
        $keys = $this->currentKeys;
        $isObject = ($type === Cursor::HASH_OBJECT);
        $isResource = ($type === Cursor::HASH_RESOURCE);

        // Build _vd metadata for objects/resources
        if ($isObject || $isResource) {
            $vd = [$type];

            // ref (object handle)
            $handle = $cursor->softRefHandle ?: $cursor->softRefTo;
            $ref = ($handle > 0) ? $handle : 0;

            // class
            $cls = ($class !== null && $class !== 'stdClass') ? $class : null;

            // prefixes array — only include if any non-public properties exist
            $prefixes = $this->buildPrefixes($keys, $result);

            // Build _vd with trailing omission: [ht] or [ht,ref] or [ht,ref,cls] or [ht,ref,cls,prefixes]
            if ($prefixes !== null) {
                $vd = [$type, $ref, $cls, $prefixes];
            } elseif ($cls !== null) {
                $vd = [$type, $ref, $cls];
            } elseif ($ref > 0) {
                $vd = [$type, $ref];
            }

            $result['_vd'] = $vd;
        }

        // Cut indicator
        if ($cut > 0) {
            $result['_cut'] = $cut;
        }

        // Pop from stack
        if ($this->stack !== []) {
            [$this->currentResult, $this->currentKeys, $this->pendingCursor, $this->currentHt] = array_pop($this->stack);
            $this->emitValue($cursor, $result);
        } else {
            $this->currentResult = null;
            $this->currentKeys = [];
            $this->pendingCursor = null;
            $this->root = $result;
        }
    }

    /**
     * Build prefixes array from the temporary prefix markers stored in result.
     * Returns null if all properties are public (no prefixes needed).
     */
    private function buildPrefixes(array $keys, array &$result): ?array
    {
        $prefixes = [];
        $hasNonPublic = false;

        foreach ($keys as $key) {
            $prefixKey = "\0_vd_p\0" . $key;
            if (isset($result[$prefixKey])) {
                $prefixes[] = $result[$prefixKey];
                unset($result[$prefixKey]);
                $hasNonPublic = true;
            } else {
                $prefixes[] = null; // public
            }
        }

        return $hasNonPublic ? $prefixes : null;
    }

    /**
     * Emit a value: either add it to the current hash, or set it as root.
     */
    private function emitValue(Cursor $cursor, mixed $value): void
    {
        if ($this->currentResult !== null) {
            $this->addToCurrentHash($cursor, $value);
        } else {
            $this->root = $value;
        }
    }

    /**
     * Add a value to the current hash with key info from the cursor.
     */
    private function addToCurrentHash(Cursor $cursor, mixed $value): void
    {
        $key = $cursor->hashKey;

        if ($key === null) {
            // Indexed array — use numeric key
            $this->currentResult[] = $value;
            return;
        }

        if ($cursor->hashKeyIsBinary) {
            $key = mb_convert_encoding($key, 'UTF-8', 'ISO-8859-1');
        }

        switch ($cursor->hashType) {
            case Cursor::HASH_INDEXED:
                $this->currentResult[] = $value;
                break;

            case Cursor::HASH_ASSOC:
                $this->currentResult[$key] = $value;
                $this->currentKeys[] = $key;
                break;

            case Cursor::HASH_RESOURCE:
                $key = "\0~\0" . $key;
                // fall through
            case Cursor::HASH_OBJECT:
                if (!isset($key[0]) || $key[0] !== "\0") {
                    // Public property
                    $this->currentResult[$key] = $value;
                    $this->currentKeys[] = $key;
                } elseif (($pos = strpos($key, "\0", 1)) !== false && $pos > 0) {
                    $prefix = substr($key, 1, $pos - 1);
                    $propName = substr($key, $pos + 1);
                    $this->currentResult[$propName] = $value;
                    $this->currentKeys[] = $propName;
                    // Store prefix marker (cleaned up in buildPrefixes)
                    if ($prefix !== '+' || true) {
                        // Always store prefix for non-public; for dynamic (+), store too
                        $this->currentResult["\0_vd_p\0" . $propName] = $prefix;
                    }
                } else {
                    $this->currentResult[$key] = $value;
                    $this->currentKeys[] = $key;
                    $this->currentResult["\0_vd_p\0" . $key] = '';
                }
                break;

            default:
                $this->currentResult[$key] = $value;
                $this->currentKeys[] = $key;
                break;
        }

        // Hard reference tracking
        if ($cursor->hardRefTo) {
            // Store as metadata — could be added to _vd later if needed
        }
    }
}
