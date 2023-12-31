<?php

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages\CreateRole;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages\EditRole;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('can create role', function () {
    $permissions = permissions();
    $name = 'Admin';
    $guardName = 'web';
    $group = $permissions
        ->groupBy(fn (Permission $permission) => Str::of($permission->name)->before('.')->value())
        ->map(fn (Collection $permissions) => $permissions->pluck('id'));

    $testable = livewire(CreateRole::class)->assertOk();
    $testable
        ->fillForm(['name' => $name, 'guard_name' => $guardName, 'permissions' => $group->toArray()])
        ->call('create')
        ->assertHasNoFormErrors();

    $id = $testable->get('form')->getRecord()->id;
    assertDatabaseHas('roles', ['name' => $name, 'guard_name' => $guardName]);
    $permissions->each(
        fn (Permission $permission) => assertDatabaseHas(
            'role_has_permissions',
            ['role_id' => $id, 'permission_id' => $permission->id]
        )
    );
});

it('can update role', function () {
    $role = givenRole();
    $name = 'Admin';
    $guardName = 'web';

    $testable = livewire(EditRole::class, ['record' => $role->id])->assertOk();
    $testable
        ->fillForm(['name' => $name, 'guard_name' => $guardName])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('roles', ['name' => $name, 'guard_name' => $guardName]);
});

it('can click select all', function () {
    $permissions = permissions();
    $role = givenUserOrRolePermissions(givenRole(), $permissions, 'users');

    $testable = livewire(EditRole::class, ['record' => $role->id])->assertOk();
    $testable->fillForm(['select_all' => true]);
    expect(getSelectedPermissions($testable->get('form'))->diff($permissions->pluck('id')))->toBeEmpty();

    $testable->call('save');
    $permissions->each(fn (Permission $permission) => assertDatabaseHas('role_has_permissions', [
        'role_id' => $role->id, 'permission_id' => $permission->id,
    ]));
});

it('can click deselect all', function () {
    $permissions = permissions();
    $role = givenUserOrRolePermissions(givenRole(), $permissions, 'users', 'roles', 'permissions');

    $testable = livewire(EditRole::class, ['record' => $role->id])->assertOk();

    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeTrue();

    $testable->fillForm(['select_all' => false]);
    expect(getCheckedPermissions($testable->get('form'))->intersect($permissions->pluck('id')))->toBeEmpty();

    $testable->call('save');
    assertDatabaseMissing('role_has_permissions', ['role_id' => $role->id]);
});

it('can click deselect users permissions and select all should be false', function () {
    $permissions = permissions();
    $role = givenUserOrRolePermissions(givenRole(), $permissions, 'users', 'roles', 'permissions');

    $testable = livewire(EditRole::class, ['record' => $role->id])->assertOk();

    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeTrue();

    tap(getCheckboxLists($form)->first(function (CheckboxList $checkboxList) {
        return Str::endsWith($checkboxList->getName(), 'users');
    }), static function (CheckboxList $checkboxList) use ($testable, $form) {
        $options = [];
        $checkboxList->state($options)->callAfterStateUpdated();
        expect($form->getState()['select_all'])->toBeFalse();

        $testable->fillForm(['permissions.users' => $options]);
    });

    $testable->call('save');
    $permissions
        ->where(fn (Permission $permission) => Str::startsWith($permission->name, 'users.'))
        ->each(fn (Permission $permission) => assertDatabaseMissing('role_has_permissions', [
            'role_id' => $role->id, 'permission_id' => $permission->id,
        ]));
});

it('click select all user permissions and select all should be true', function () {
    $permissions = permissions();
    $role = givenUserOrRolePermissions(givenRole(), $permissions, 'roles', 'permissions');

    $testable = livewire(EditRole::class, ['record' => $role->id])->assertOk();

    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeFalse();

    tap(getCheckboxLists($form)->first(function (CheckboxList $checkboxList) {
        return Str::endsWith($checkboxList->getName(), 'users');
    }), static function (CheckboxList $checkboxList) use ($testable, $form) {
        $options = array_keys($checkboxList->getOptions());
        $checkboxList->state($options)->callAfterStateUpdated();
        expect($form->getState()['select_all'])->toBeTrue();

        $testable->fillForm(['permissions.users' => $options]);
    });

    $testable->call('save');
    $permissions->each(fn (Permission $permission) => assertDatabaseHas('role_has_permissions', [
        'role_id' => $role->id, 'permission_id' => $permission->id,
    ]));
});
