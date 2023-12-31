<?php

namespace Recca0120\FilamentPermission\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\SpatieLaravelSettingsPluginServiceProvider;
use Filament\SpatieLaravelTranslatablePluginServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Recca0120\FilamentPermission\FilamentPermissionServiceProvider;
use Recca0120\FilamentPermission\Tests\Fixtures\FilamentPermissionPanelProvider;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\User;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Recca0120\\FilamentPermission\\Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            //            SpatieLaravelSettingsPluginServiceProvider::class,
            //            SpatieLaravelTranslatablePluginServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            PermissionServiceProvider::class,
            FilamentPermissionServiceProvider::class,
            FilamentPermissionPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:' . base64_encode(Encrypter::generateKey(config('config.app.cipher'))));
        config()->set('app.debug', true);
        config()->set('auth.providers.users.model', User::class);

        $migration = new class extends Migration
        {
            public function up(): void
            {
                Schema::create('users', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email')->unique();
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password');
                    $table->rememberToken();
                    $table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('users');
            }
        };
        $migration->up();

        $migration = include __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $migration->up();
        /*
        $migration = include __DIR__.'/../database/migrations/create_filament-permission_table.php.stub';
        $migration->up();
        */
    }
}
