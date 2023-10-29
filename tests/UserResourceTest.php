<?php

use Filament\Forms\Form;
use Illuminate\Support\Facades\Hash;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\CreateUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\EditUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\Role;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\User;
use function Pest\Laravel\assertDatabaseHas;
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
