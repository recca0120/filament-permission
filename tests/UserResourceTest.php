<?php

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\CreateUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\EditUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\Role;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\User;
use Spatie\Permission\Models\Permission;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(fn() => Role::create(['name' => fake()->name(), 'guard_name' => 'web']));

test('can create user', function () {
    $testable = livewire(CreateUser::class)->assertOk();

    $email = 'foo@test.com';
    $name = 'foo';
    $password = 'password';

    $testable
        ->fillForm(['email' => $email, 'name' => $name, 'password' => $password])
        ->call('create')
        ->assertHasNoFormErrors();

    /** @var Form $form */
    $form = $testable->get('form');

    assertDatabaseHas('users', ['email' => $email, 'name' => $name]);

    /** @var User $user */
    $user = User::query()->where('email', $email)->sole();
    expect(Hash::check($password, $user->password))->toBeTrue();
});

test('can update user', function () {
    /** @var \App\Models\User $user */
    $user = User::create([
        'name' => fake()->name(),
        'email' => fake()->email(),
        'password' => fake()->password(8),
    ]);
    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    $email = 'foo@test.com';
    $name = 'foo';

    $testable
        ->fillForm(['email' => $email, 'name' => $name])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('users', ['email' => $email, 'name' => $name]);
});

it('can click select all', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'users');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();
    $testable->fillForm(['select_all' => true]);
    expect(getSelectedPermissions($testable->get('form'))->diff($permissions->pluck('id')))->toBeEmpty();

    $testable->call('save');
    $permissions->each(fn(Permission $permission) => assertDatabaseHas('model_has_permissions', [
        'model_type' => User::class, 'model_id' => $user->id, 'permission_id' => $permission->id,
    ]));
});

it('can click deselect all', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'users', 'roles', 'permissions');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeTrue();

    $testable->fillForm(['select_all' => false]);
    expect(getCheckedPermissions($testable->get('form'))->intersect($permissions->pluck('id')))->toBeEmpty();

    $testable->call('save');
    assertDatabaseMissing('model_has_permissions', ['model_type' => User::class, 'model_id' => $user->id]);
});

it('can click deselect users permissions and select all should be false', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'users', 'roles', 'permissions');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

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
        ->where(fn(Permission $permission) => Str::startsWith($permission->name, 'users.'))
        ->each(fn(Permission $permission) => assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class, 'model_id' => $user->id, 'permission_id' => $permission->id,
        ]));
});

it('click select all user permissions and select all should be true', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'roles', 'permissions');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

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
    $permissions->each(fn(Permission $permission) => assertDatabaseHas('model_has_permissions', [
        'model_type' => User::class, 'model_id' => $user->id, 'permission_id' => $permission->id,
    ]));
});

it('add user role', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'permissions');
    $role = givenUserOrRolePermissions(givenRole(), $permissions, 'users');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    $testable
        ->fillForm(['roles' => [$role->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('model_has_roles', [
        'model_type' => User::class, 'model_id' => $user->id, 'role_id' => $role->id,
    ]);
});

it('add user and permission roles', function () {
    $permissions = permissions();
    $user = givenUserOrRolePermissions(givenUser(), $permissions, 'permissions');
    $userRole = givenUserOrRolePermissions(givenRole(), $permissions, 'user');
    $permissionRole = givenUserOrRolePermissions(givenRole(), $permissions, 'permissions');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    $testable
        ->fillForm(['roles' => [$userRole->id, $permissionRole->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('model_has_roles', [
        'model_type' => User::class, 'model_id' => $user->id, 'role_id' => $userRole->id,
    ]);
    assertDatabaseHas('model_has_roles', [
        'model_type' => User::class, 'model_id' => $user->id, 'role_id' => $permissionRole->id,
    ]);
    $permissions->filter(function (Permission $permission) {
        return Str::startsWith($permission->name, 'permissions');
    })->each(function (Permission $permission) use ($user) {
        assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class, 'model_id' => $user->id, 'permission_id' => $permission->id,
        ]);
    });
});

it('toggle select all when role selected', function () {
    $permissions = permissions();
    $user = givenUser();
    $userRole = givenUserOrRolePermissions(givenRole(), $permissions, 'users');
    $otherRole = givenUserOrRolePermissions(givenRole(), $permissions, 'roles', 'permissions');

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    $testable->fillForm(['roles' => [$userRole->id, $otherRole->id]]);
    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeTrue();

    $testable->fillForm(['roles' => []]);
    /** @var Form $form */
    $form = $testable->get('form');
    expect($form->getState()['select_all'])->toBeFalse();
});

it('keep users permissions', function () {
    $permissions = permissions();
    $userRole = givenUserOrRolePermissions(givenRole(), $permissions, 'users');
    $user = givenUser();
    $user->assignRole($userRole);

    $testable = livewire(EditUser::class, ['record' => $user->id])->assertOk();

    $testable
        ->fillForm(['permissions' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('model_has_roles', [
        'model_type' => User::class, 'model_id' => $user->id, 'role_id' => $userRole->id,
    ]);

    $expected = $user->getAllPermissions()->pluck('id')->values()->sort()->toArray();
    $actual = $permissions
        ->filter(fn(Permission $permission) => Str::startsWith($permission->name, 'users'))
        ->pluck('id')
        ->values()
        ->sort()
        ->toArray();

    expect($expected)->toMatchArray($actual);
});
