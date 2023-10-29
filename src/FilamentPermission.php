<?php

namespace Recca0120\FilamentPermission;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class FilamentPermission
{
    /**
     * @var Collection<int, Permission>
     */
    private ?\Illuminate\Database\Eloquent\Collection $cachedAllPermissions = null;

    public function toggleRoles(array $roles): array
    {
        return self::permissionGroupByPrefix($this->getCachedAllPermissions())
            ->map(function (Collection $permissions) use ($roles) {
                return $permissions->filter(function (Permission $permission) use ($roles) {
                    return $permission->roles->pluck('id')->intersect($roles)->isNotEmpty();
                })->pluck('id')->unique()->values()->toArray();
            })->toArray();
    }

    public function checkAllCheckboxesAreChecked(array $permissionGroup): bool
    {
        return $this->getCachedAllPermissions()
            ->pluck('id')
            ->diff(collect($permissionGroup)->collapse())
            ->isEmpty();
    }

    public function toggleAll(bool $state): array
    {
        $permissions = $state ? $this->getCachedAllPermissions() : collect();

        return self::permissionGroupByPrefix($permissions)
            ->map(fn(Collection $group) => $group->pluck('id'))
            ->toArray();
    }

    private function getCachedAllPermissions(): Collection
    {
        if ($this->cachedAllPermissions) {
            return $this->cachedAllPermissions;
        }

        return $this->cachedAllPermissions = Permission::with('roles')->get();
    }

    public static function permissionGroupByPrefix(Collection $permissions): Collection
    {
        return $permissions->groupBy(function (Permission $permission) {
            return Str::of($permission->name)->before('.')->value();
        });
    }
}
