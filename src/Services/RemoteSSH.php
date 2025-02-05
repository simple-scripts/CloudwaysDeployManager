<?php

namespace SimpleScripts\CloudDeployManager\Services;

use Illuminate\Console\OutputStyle;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Ssh\PlinkOrProcessSsh;
use Spatie\Ssh\Ssh;

class RemoteSSH
{
    protected array $commands = [];

    protected CloudwaysApp $cloudwaysApp;

    protected ?OutputStyle $output = null;

    protected Ssh $ssh_connection;

    public function __construct(CloudwaysApp $cloudwaysApp)
    {
        $this->cloudwaysApp = $cloudwaysApp;
    }

    public function addCommand(string $command): static
    {
        $this->commands[] = $command;

        return $this;
    }

    public function cdToApplication(): static
    {
        $this->commands[] = 'cd '.$this->cloudwaysApp->path;

        return $this;
    }

    public function composerInstallNoDev(bool $discard_changes = true): static
    {
        if ($discard_changes) {
            $this->commands[] = 'composer config --global discard-changes true';
        }
        $this->commands[] = 'composer install --no-dev --no-interaction';

        return $this;
    }

    public function laravelArtisanClear(): static
    {
        $this->commands[] = 'php artisan config:clear && php artisan view:clear && php artisan route:clear';

        return $this;
    }

    public function laravelArtisanCache(): static
    {
        $this->commands[] = 'php artisan config:cache && php artisan view:cache && php artisan route:cache';

        return $this;
    }

    public function laravelArtisanMigrate(bool $force = true): static
    {
        $this->commands[] = 'php artisan migrate'.($force ? ' --force' : '');

        return $this;
    }

    public function getSshCommandAsString(bool $include_batch = true): string
    {
        $ssh = $this->loadSsh();

        if ($ssh->isPlink()) {
            // print_r('Windows Plink!');echo PHP_EOL;
            // Windows:
            return $ssh->buildPlinkExecuteCommand($this->commands, $include_batch);
        }

        return $ssh->getExecuteCommand($this->commands);
    }

    public function runCommands()
    {
        if ($this->output) {
            $this->output->text('Starting remote terminal commands:');
            foreach ($this->commands as $command) {
                $this->output->text('  '.$command);
            }
        }
        $ssh = $this->loadSsh();

        if ($ssh->isPlink()) {
            // print_r('Windows Plink!');echo PHP_EOL;
            // Windows:
            return $ssh->runExec($this->commands);
        }

        $ssh->removeBash();

        return $ssh->execute($this->commands);
    }

    public function uploadComposerAuthJson(string $sourcePath, string $destinationPath = '~/.composer/auth.json')
    {
        $ssh = $this->loadSsh(); // true);

        return $ssh->uploadLocalFile($sourcePath, $destinationPath);
    }

    protected function loadSsh(bool $force_raw_key = false): PlinkOrProcessSsh
    {
        $sshClass = PlinkOrProcessSsh::class;
        $ssh = new $sshClass(
            $this->cloudwaysApp->server->username,
            $this->cloudwaysApp->server->ip_address
        );
        $key = 'cw-deploy-manager.ssh.private_key_path';
        if (! $force_raw_key && $ssh->isPlink()) {
            // didn't get the key to work with scp
            // $key = 'cw-deploy-manager.ssh.private_ppk_key_path';
        }
        $ssh
            ->setCloudwaysApp($this->cloudwaysApp)
            ->usePrivateKey('"'.config($key).'"')
            ->onOutput(function ($type, $line) {
                echo $line;
            });

        return $ssh;
    }

    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }
}
