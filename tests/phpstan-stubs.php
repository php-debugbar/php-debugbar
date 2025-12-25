<?php

declare(strict_types=1);

namespace Symfony\Component\Panther;

abstract class PantherTestCase extends \PHPUnit\Framework\TestCase
{
    public static function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): Client
    {
        return Client::createChromeClient();
    }
}
