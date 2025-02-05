<?php

namespace SimpleScripts\CloudDeployManager;

use Carbon\Carbon;
use SimpleScripts\CloudDeployManager\Console\Commands\CloudwaysComposerAuthCommand;
use SimpleScripts\CloudDeployManager\Console\Commands\CloudwaysExport;
use SimpleScripts\CloudDeployManager\Console\Commands\CloudwaysImport;
use SimpleScripts\CloudDeployManager\Console\Commands\CloudwaysListCommand;
use SimpleScripts\CloudDeployManager\Console\Commands\CloudwaysUploadSsh;
use SimpleScripts\CloudDeployManager\Console\Commands\LaravelDeploy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CloudDeployManagerServiceProvider extends PackageServiceProvider
{
    /**
     * List of commands to register
     *
     * @private array $commands
     */
    protected array $commands = [
        CloudwaysComposerAuthCommand::class,
        CloudwaysExport::class,
        CloudwaysImport::class,
        CloudwaysListCommand::class,
        CloudwaysUploadSsh::class,
        LaravelDeploy::class,
    ];

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('clouddeploymanager')
            ->hasConfigFile('cw-deploy-manager')
            ->hasCommands($this->commands)
            ->hasMigrations([
                '2024_10_05_212420_cloud_ways_server',
                '2024_10_05_212431_cloud_ways_app',
            ]);
    }

    /**
     * Generate a migration name.
     */
    protected function generateMigrationName(string $migrationFileName, Carbon $now): string
    {
        // Keep the assigned date
        $pattern = '^[0-9]{4}_[0-9]{2}_[0-9]{2}(.)+'; //(\.php$)
        if (preg_match('/'.$pattern.'/', $migrationFileName)) {
            return database_path('migrations/'.$migrationFileName.'.php');
        }

        return parent::generateMigrationName($migrationFileName, $now);
    }
}
