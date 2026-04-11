<?php

namespace MarianDumitru\Netopay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarianDumitru\Netopay\Netopay
 */
class Netopay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MarianDumitru\Netopay\Netopay::class;
    }
}
