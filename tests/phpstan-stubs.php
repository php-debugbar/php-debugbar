<?php

namespace Symfony\Component\Panther;


abstract class PantherTestCase extends \PHPUnit\Framework\TestCase
{
    public static function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = [])
    {
        return Client::createChromeClient();
    }
}