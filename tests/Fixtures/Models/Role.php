<?php

namespace Recca0120\FilamentPermission\Tests\Fixtures\Models;

use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $fillable = ['name', 'guard_name'];
}
