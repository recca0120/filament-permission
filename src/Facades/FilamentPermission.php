<?php

namespace Recca0120\FilamentPermission\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Recca0120\FilamentPermission\FilamentPermission
 *
 * @method static array toggleRoles(array $roles)
 * @method static bool checkAllCheckboxesAreChecked(array $permissionGroup)
 * @method static array toggleAll(bool $state)
 */
class FilamentPermission extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Recca0120\FilamentPermission\FilamentPermission::class;
    }
}
