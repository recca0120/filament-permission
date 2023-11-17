<?php

namespace Recca0120\FilamentPermission;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Recca0120\FilamentPermission\Commands\FilamentPermissionCommand;
use Recca0120\FilamentPermission\Testing\TestsFilamentPermission;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPermissionServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-permission';

    public static string $viewNamespace = 'filament-permission';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
//            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
//                    ->publishMigrations()
//                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('recca0120/filament-permission');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        // Asset Registration
        // FilamentAsset::register(
        //     $this->getAssets(),
        //     $this->getAssetPackageName()
        // );
        //
        // FilamentAsset::registerScriptData(
        //     $this->getScriptData(),
        //     $this->getAssetPackageName()
        // );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-permission/{$file->getFilename()}"),
                ], 'filament-permission-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentPermission());
    }

    protected function getAssetPackageName(): ?string
    {
        return 'recca0120/filament-permission';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('filament-permission', __DIR__ . '/../resources/dist/components/filament-permission.js'),
            Css::make('filament-permission-styles', __DIR__ . '/../resources/dist/filament-permission.css'),
            Js::make('filament-permission-scripts', __DIR__ . '/../resources/dist/filament-permission.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentPermissionCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            // 'create_filament-permission_table',
        ];
    }
}
