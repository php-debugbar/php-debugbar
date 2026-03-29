<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter\VarDumper;

/**
 * Wraps a JSON node from DebugBarJsonDumper so it can be cloned by VarCloner
 * and dumped through any standard Symfony dumper (CliDumper, HtmlDumper).
 *
 * Register the companion caster on your VarCloner:
 *
 *     $cloner = new VarCloner();
 *     $cloner->addCasters(DebugBarJsonCaster::getCasters());
 *     $data = $cloner->cloneVar(new DebugBarJsonVar($jsonNode));
 */
class DebugBarJsonVar
{
    public function __construct(
        public readonly array $node,
    ) {}
}
