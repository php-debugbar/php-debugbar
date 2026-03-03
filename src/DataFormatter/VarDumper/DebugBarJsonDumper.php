<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\DumperInterface;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * Dumps variables as JSON-serializable arrays instead of HTML strings.
 *
 * Implements Symfony's DumperInterface (the callback interface used by Data::dump())
 * and DataDumperInterface (the high-level dump(Data) interface).
 *
 * The output is a tree of nodes with short keys for compactness:
 *  - Scalar: {t:"s", st:<subtype>, v:<value>, a:<attrs>}
 *  - String: {t:"r", v:<string>, bin:true, cut:<n>, len:<n>}
 *  - Hash:   {t:"h", ht:<type>, cls:<class>, depth:<n>, children:[...], cut:<n>, ref:<ref>}
 */
class DebugBarJsonDumper implements DumperInterface, DataDumperInterface
{
    /** @var array Stack of hash nodes being built */
    private array $stack = [];

    /** @var array|null The root node after dumping */
    private ?array $root = null;

    /** @var array|null Current hash node being populated */
    private ?array $currentHash = null;

    /** @var Cursor|null Cursor state for the current item (used to extract key info) */
    private ?Cursor $pendingCursor = null;

    /**
     * Dump a Data object and return the JSON string.
     */
    public function dump(Data $data): ?string
    {
        $array = $this->dumpAsArray($data);
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Dump a Data object and return the raw PHP array (avoids double-encoding).
     */
    public function dumpAsArray(Data $data): array
    {
        $this->stack = [];
        $this->root = null;
        $this->currentHash = null;
        $this->pendingCursor = null;

        $data->dump($this);

        return $this->root ?? ['t' => 's', 'st' => 'NULL', 'v' => null];
    }

    public function dumpScalar(Cursor $cursor, string $type, string|int|float|bool|null $value): void
    {
        $node = [
            't' => 's',
            'st' => $type,
            'v' => $value,
        ];

        if ($cursor->attr) {
            $node['a'] = $cursor->attr;
        }

        $this->emitNode($cursor, $node);
    }

    public function dumpString(Cursor $cursor, string $str, bool $bin, int $cut): void
    {
        $node = [
            't' => 'r',
            'v' => $str,
        ];

        if ($bin) {
            $node['bin'] = true;
        }
        if ($cut > 0) {
            $node['cut'] = $cut;
            $node['len'] = mb_strlen($str, $bin ? '8bit' : 'UTF-8') + $cut;
        }

        $this->emitNode($cursor, $node);
    }

    public function enterHash(Cursor $cursor, int $type, string|int|null $class, bool $hasChild): void
    {
        $node = [
            't' => 'h',
            'ht' => $type,
            'cls' => $class,
            'depth' => $cursor->depth,
            'children' => [],
        ];

        // Track soft references (object/resource identity)
        if ($cursor->softRefTo) {
            $node['ref'] = ['s' => $cursor->softRefTo, 'c' => $cursor->softRefCount];
        }

        // Push current hash onto stack
        if ($this->currentHash !== null) {
            $this->stack[] = [$this->currentHash, $this->pendingCursor];
        }

        $this->currentHash = $node;
        $this->pendingCursor = clone $cursor;
    }

    public function leaveHash(Cursor $cursor, int $type, string|int|null $class, bool $hasChild, int $cut): void
    {
        $node = $this->currentHash;

        if ($cut > 0) {
            $node['cut'] = $cut;
        }

        // Pop from stack
        if (!empty($this->stack)) {
            [$this->currentHash, $this->pendingCursor] = array_pop($this->stack);
            // Emit the completed hash node as a child of the parent
            $this->emitNode($cursor, $node);
        } else {
            $this->currentHash = null;
            $this->pendingCursor = null;
            $this->root = $node;
        }
    }

    /**
     * Emit a node: either add it as a child to the current hash, or set it as root.
     */
    private function emitNode(Cursor $cursor, array $node): void
    {
        if ($this->currentHash !== null) {
            $entry = $this->buildEntry($cursor, $node);
            $this->currentHash['children'][] = $entry;
        } else {
            $this->root = $node;
        }
    }

    /**
     * Build a child entry with key information extracted from the cursor.
     * Key parsing follows the \0-encoded visibility pattern from CliDumper::dumpKey().
     */
    private function buildEntry(Cursor $cursor, array $node): array
    {
        $entry = ['n' => $node];

        $key = $cursor->hashKey;

        if ($key === null) {
            return $entry;
        }

        if ($cursor->hashKeyIsBinary) {
            $key = mb_convert_encoding($key, 'UTF-8', 'ISO-8859-1');
        }

        // Hard reference tracking
        if ($cursor->hardRefTo) {
            $entry['ref'] = $cursor->hardRefTo;
        }

        switch ($cursor->hashType) {
            case Cursor::HASH_INDEXED:
                $entry['k'] = $key;
                $entry['kt'] = 'index';
                break;

            case Cursor::HASH_ASSOC:
                $entry['k'] = $key;
                $entry['kt'] = is_int($key) ? 'index' : 'key';
                break;

            case Cursor::HASH_RESOURCE:
                $key = "\0~\0" . $key;
                // fall through
            case Cursor::HASH_OBJECT:
                if (!isset($key[0]) || $key[0] !== "\0") {
                    // Public property
                    $entry['k'] = $key;
                    $entry['kt'] = 'public';
                } elseif (($pos = strpos($key, "\0", 1)) !== false && $pos > 0) {
                    $prefix = substr($key, 1, $pos - 1);
                    $propName = substr($key, $pos + 1);

                    switch ($prefix[0]) {
                        case '+': // Dynamic property
                            $entry['k'] = $propName;
                            $entry['kt'] = 'public';
                            break;
                        case '~': // Meta property
                            $entry['k'] = $propName;
                            $entry['kt'] = 'meta';
                            break;
                        case '*': // Protected property
                            $entry['k'] = $propName;
                            $entry['kt'] = 'protected';
                            break;
                        default: // Private property (prefix is the declaring class)
                            $entry['k'] = $propName;
                            $entry['kt'] = 'private';
                            $entry['kc'] = $prefix;
                            break;
                    }
                } else {
                    // Fallback: private with unknown class
                    $entry['k'] = $key;
                    $entry['kt'] = 'private';
                    $entry['kc'] = '';
                }
                break;

            default:
                $entry['k'] = $key;
                $entry['kt'] = 'key';
                break;
        }

        return $entry;
    }
}
