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
 *  - Scalar: {t:"s", s:<subtype>, v:<value>, a:<attrs>}  (s: b=bool, i=int, d=double, n=null, l=label)
 *  - String: {t:"r", v:<string>, bin:true, cut:<n>, len:<n>}
 *  - Hash:   {t:"h", ht:<type>, cls:<class>, d:<depth>, c:[...], cut:<n>, ref:<ref>}
 *  - Entry:  {n:<node>, k?:<key>, kt?:<keytype>}  (inferrable fields omitted for compactness)
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
     *
     * @return array{
     *     t: 's', s: string, v: mixed, a?: array<string, mixed>
     * }|array{
     *     t: 'r', v: string, bin?: true, cut?: int, len?: int
     * }|array{
     *     t: 'h', ht: int, cls?: string, d: int, c?: list<array{
     *         n: array<string, mixed>, k?: string|int, kt?: string, kc?: string, dyn?: true, ref?: int
     *     }>, cut?: int, ref?: array{s: int, c: int}
     * }
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

    private const SCALAR_TYPE_MAP = [
        'boolean' => 'b',
        'integer' => 'i',
        'double' => 'd',
        'NULL' => 'n',
        'label' => 'l',
    ];

    public function dumpScalar(Cursor $cursor, string $type, $value): void
    {
        $node = [
            't' => 's',
            's' => self::SCALAR_TYPE_MAP[$type] ?? $type,
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

    public function enterHash(Cursor $cursor, int $type, $class, bool $hasChild): void
    {
        $node = [
            't' => 'h',
            'ht' => $type,
            'd' => $cursor->depth,
        ];

        // Omit class for stdClass (matches Symfony's behavior)
        if ($class !== null && $class !== 'stdClass') {
            $node['cls'] = $class;
        }

        // Track object/resource identity (softRefHandle is the display ID #N)
        $handle = $cursor->softRefHandle ?: $cursor->softRefTo;
        if ($handle > 0) {
            $node['ref'] = ['s' => $handle, 'c' => $cursor->softRefCount];
        }

        // Push current hash onto stack
        if ($this->currentHash !== null) {
            $this->stack[] = [$this->currentHash, $this->pendingCursor];
        }

        $this->currentHash = $node;
        $this->pendingCursor = clone $cursor;
    }

    public function leaveHash(Cursor $cursor, int $type, $class, bool $hasChild, int $cut): void
    {
        $node = $this->currentHash;

        if ($cut > 0) {
            $node['cut'] = $cut;
        }

        // Pop from stack
        if ($this->stack !== []) {
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
            $this->currentHash['c'] ??= [];
            $this->currentHash['c'][] = $entry;
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
                // Both k and kt are inferrable (k from position, kt='i' from ht=2)
                break;

            case Cursor::HASH_ASSOC:
                // kt is inferrable from typeof k (int→'i', string→'k')
                $entry['k'] = $key;
                break;

            case Cursor::HASH_RESOURCE:
                $key = "\0~\0" . $key;
                // fall through
                // no break
            case Cursor::HASH_OBJECT:
                if (!isset($key[0]) || $key[0] !== "\0") {
                    // Public property — kt='pub' is the default for objects, omit it
                    $entry['k'] = $key;
                } elseif (($pos = strpos($key, "\0", 1)) !== false && $pos > 0) {
                    $prefix = substr($key, 1, $pos - 1);
                    $propName = substr($key, $pos + 1);

                    switch ($prefix[0]) {
                        case '+': // Dynamic property — kt='pub' is default, omit it
                            $entry['k'] = $propName;
                            $entry['dyn'] = true;
                            break;
                        case '~': // Meta property — must be explicit
                            $entry['k'] = $propName;
                            $entry['kt'] = 'meta';
                            break;
                        case '*': // Protected property — must be explicit
                            $entry['k'] = $propName;
                            $entry['kt'] = 'pro';
                            break;
                        default: // Private property — must be explicit
                            $entry['k'] = $propName;
                            $entry['kt'] = 'pri';
                            $entry['kc'] = $prefix;
                            break;
                    }
                } else {
                    // Fallback: private with unknown class
                    $entry['k'] = $key;
                    $entry['kt'] = 'pri';
                    $entry['kc'] = '';
                }
                break;

            default:
                $entry['k'] = $key;
                $entry['kt'] = 'k';
                break;
        }

        return $entry;
    }
}
