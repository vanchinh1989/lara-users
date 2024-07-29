<?php

namespace vanchinh1989\larausers;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use vanchinh1989\larausers\App\Http\Controllers\UsersController;

class LaravelUsersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lara-users')
            ->hasConfigFile('larausers')
            ->hasTranslations()
            ->hasAssets()
            ->hasRoute('api')
            ->hasMigrations(['create_users_table','create_profiles_table'])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->make('vanchinh1989\larausers\App\Http\Controllers\UsersController');
        $this->app->singleton(UsersController::class, function () {
            return new App\Http\Controllers\UsersController();
        });
        $this->app->alias(UsersController::class, 'larausers');
    }
}