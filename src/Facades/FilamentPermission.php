<?php

namespace Recca0120\FilamentPermission\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Recca0120\FilamentPermission\FilamentPermission
 */
class FilamentPermission extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Recca0120\FilamentPermission\FilamentPermission::class;
    }
}
