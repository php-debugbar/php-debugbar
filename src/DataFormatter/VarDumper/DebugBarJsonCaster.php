<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Caster that teaches VarCloner how to present DebugBarJsonVar nodes
 * as if they were the original PHP values.
 *
 * Usage:
 *     $cloner = new VarCloner();
 *     $cloner->addCasters(DebugBarJsonCaster::getCasters());
 *     $data = $cloner->cloneVar(new DebugBarJsonVar($jsonNode));
 *     (new HtmlDumper())->dump($data);
 */
class DebugBarJsonCaster
{
    /**
     * Returns the caster map to register with VarCloner::addCasters().
     */
    public static function getCasters(): array
    {
        return [
            DebugBarJsonVar::class => [self::class, 'cast'],
        ];
    }

    public static function cast(DebugBarJsonVar $var, array $a, Stub $stub, bool $isNested): array
    {
        $node = $var->node;

        return match ($node['t'] ?? null) {
            's' => self::castScalar($node, $stub),
            'r' => self::castString($node, $stub),
            'h' => self::castHash($node, $stub),
            default => [],
        };
    }

    private static function castScalar(array $node, Stub $stub): array
    {
        // Use TYPE_REF so Data::dumpItem unwraps to the native PHP value,
        // matching how VarCloner stores scalars natively.
        $stub->type = Stub::TYPE_REF;
        $stub->class = '';
        $stub->handle = 0;
        $stub->value = match ($node['s']) {
            'b' => (bool) $node['v'],
            'i' => (int) $node['v'],
            'd' => (float) $node['v'],
            'n' => null,
            default => $node['v'] ?? null,
        };

        return [];
    }

    private static function castString(array $node, Stub $stub): array
    {
        $stub->type = Stub::TYPE_STRING;
        $stub->class = ($node['bin'] ?? false) ? Stub::STRING_BINARY : Stub::STRING_UTF8;
        $stub->value = $node['v'];
        $stub->cut = $node['cut'] ?? 0;

        return [];
    }

    private static function castHash(array $node, Stub $stub): array
    {
        $ht = $node['ht'];
        $children = $node['c'] ?? [];
        $cut = $node['cut'] ?? 0;

        if ($ht === Cursor::HASH_OBJECT) {
            $stub->type = Stub::TYPE_OBJECT;
            $stub->class = $node['cls'] ?? 'stdClass';
            if (isset($node['ref'])) {
                $ref = $node['ref'];
                $stub->handle = is_array($ref) ? $ref['s'] : $ref;
                $stub->refCount = is_array($ref) ? $ref['c'] : 0;
            } else {
                $stub->handle = 0;
            }
        } elseif ($ht === Cursor::HASH_RESOURCE) {
            $stub->type = Stub::TYPE_RESOURCE;
            $stub->class = $node['cls'] ?? 'Unknown';
            $stub->handle = 0;
        } else {
            // For TYPE_ARRAY, Data::dumpItem copies class→type and value→class,
            // so class must be ARRAY_INDEXED/ARRAY_ASSOC and value is the count.
            $stub->type = Stub::TYPE_ARRAY;
            $stub->class = ($ht === Cursor::HASH_INDEXED) ? Stub::ARRAY_INDEXED : Stub::ARRAY_ASSOC;
            $stub->value = count($children) + $cut;
            $stub->handle = 0;
        }

        $stub->cut = $cut;

        $a = [];
        foreach ($children as $i => $entry) {
            $key = self::buildKey($entry, $ht, $i);
            $a[$key] = self::nodeToValue($entry['n']);
        }

        return $a;
    }

    private static function buildKey(array $entry, int $ht, int $index): string|int
    {
        $k = $entry['k'] ?? $index;

        // Compact format: single prefix field for object/resource visibility
        if (isset($entry['p'])) {
            return match ($entry['p']) {
                '+' => Caster::PREFIX_DYNAMIC . $k,
                '~' => Caster::PREFIX_VIRTUAL . $k,
                '*' => Caster::PREFIX_PROTECTED . $k,
                '' => sprintf(Caster::PATTERN_PRIVATE, '', $k),
                default => sprintf(Caster::PATTERN_PRIVATE, $entry['p'], $k),
            };
        }

        // Legacy format (deprecated): kt/kc/dyn fields
        $kt = $entry['kt'] ?? null;
        $isDynamic = ($entry['dyn'] ?? false) === true;

        // Infer key type from parent hash type when not explicit
        if ($kt === null) {
            if ($ht === Cursor::HASH_INDEXED) {
                return (int) $k;
            }
            if ($ht === Cursor::HASH_OBJECT) {
                return $isDynamic ? Caster::PREFIX_DYNAMIC . $k : $k;
            }
            if ($ht === Cursor::HASH_RESOURCE) {
                return Caster::PREFIX_VIRTUAL . $k;
            }
            return $k;
        }

        return match ($kt) {
            'i' => (int) $k,
            'pub' => $isDynamic ? Caster::PREFIX_DYNAMIC . $k : $k,
            'pro' => Caster::PREFIX_PROTECTED . $k,
            'pri' => sprintf(Caster::PATTERN_PRIVATE, $entry['kc'] ?? '', $k),
            'meta' => Caster::PREFIX_VIRTUAL . $k,
            default => $k, // 'k' and others
        };
    }

    private static function nodeToValue(array $node): mixed
    {
        $type = $node['t'] ?? null;

        // Scalars and strings become native PHP values — VarCloner handles them directly
        if ($type === 's') {
            return match ($node['s']) {
                'b' => (bool) $node['v'],
                'i' => (int) $node['v'],
                'd' => (float) $node['v'],
                'n' => null,
                'l' => $node['v'] ?? '',
                default => $node['v'] ?? null,
            };
        }

        if ($type === 'r') {
            return $node['v'];
        }

        // Hash nodes wrap in DebugBarJsonVar so the caster fires recursively
        if ($type === 'h') {
            return new DebugBarJsonVar($node);
        }

        return null;
    }
}
