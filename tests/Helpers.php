<?php

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\Role;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\User;
use Spatie\Permission\Models\Permission;

function permissions()
{
    return collect([
        givenPermissions('permissions'),
        givenPermissions('roles'),
        givenPermissions('users'),
    ])->collapse();
}

function givenUser(): User
{
    return User::create(['name' => fake()->name(), 'email' => fake()->email(), 'password' => fake()->password(8)]);
}

function givenRole(): Role
{
    return Role::create(['name' => fake()->name, 'guard_name' => 'web']);
}

function givenPermissions(string $entity): Collection
{
    return collect(range(0, 4))->map(function ($index) use ($entity) {
        return Permission::create(['name' => $entity.'.'.$entity.'-'.$index, 'guard_name' => 'web']);
    });
}

function getSelectedPermissions(Form $form): Collection
{
    return getCheckboxLists($form)
        ->map(fn(CheckboxList $checkboxList) => $checkboxList->getState())
        ->collapse()
        ->values();
}

function getCheckboxLists(Form $form): Collection
{
    return collect($form->getFlatComponents())
        ->filter(fn(Component $component) => is_a($component, CheckboxList::class));
}

function getCheckedPermissions(Form $form): Collection
{
    return getCheckboxLists($form)
        ->map(fn(CheckboxList $checkboxList) => $checkboxList->getState())
        ->collapse()
        ->values();
}

function givenUserOrRolePermissions(User|Role $userOrRole, Collection $permissions, string ...$names): User|Role
{
    $allowed = $permissions->filter(function (Permission $permission) use ($names) {
        $prefix = Str::of($permission->name)->before('.')->value();

        return in_array($prefix, $names);
    });

    return $userOrRole->syncPermissions($allowed->pluck('id')->toArray());
}
