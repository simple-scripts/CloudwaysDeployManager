<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use SimpleScripts\CloudDeployManager\Events\CloudwaysDeployLaravelPrependRemoteSsh;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\RemoteSSH;
use Symfony\Component\Process\Process;

class LaravelDeploy extends Command
{
    use CloudwaysDeployGitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:deploy:laravel
        {--g|group= : As defined in the CloudApp->group column }
        {--t|type=dev : local, dev, stage or prod }
        {--s|short_codes= : Limit to a comma seperated list of App\Models\CloudApp short codes to run }
        {--i|ids= : Limit to a comma seperated list of App\Models\CloudApp IDs to run }
        {--b|branch= : git branch to pull from, defaults to what is in the DB }
        {--d|discard=0 : Set composer config --global discard-changes true }
        {--c|cache=1 : Build the cache after steps, 1 or 0 }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all commands to deploy a laravel app on Cloudways via SSH';

    /**
     * Execute the console command.
     *
     * @throws RequestException
     */
    public function handle(): void
    {
        if (! $this->validateEnv()) {
            return;
        }

        $branch = $this->input->getOption('branch');
        $cache = (bool) $this->input->getOption('cache');
        $discard = (bool) $this->input->getOption('discard');

        $appCollection = $this->getCloudwaysAppCollectionFromCliInput();

        /** @var CloudwaysApp $cloudwaysApp */
        foreach ($appCollection as $cloudwaysApp) {
            if (! empty($branch)) {
                $cloudwaysApp->git_branch = $branch;
            }
            $this->info('--------'.PHP_EOL.'Starting: '.$cloudwaysApp->id.' - '.$cloudwaysApp->name.' on branch: '
                .$cloudwaysApp->git_branch.' and URL: '.$cloudwaysApp->url);

            // 1. clear existing cache for config, routes and views:
            $remoteSsh = new RemoteSSH($cloudwaysApp);
            $remoteSsh->setOutput($this->getOutput());
            $process_or_string = $remoteSsh
                ->cdToApplication()
                ->laravelArtisanClear()
                ->runCommands();

            $this->outputSshProcess($process_or_string);

            // 2. git deploy
            $this->deployGitBranchToServer($cloudwaysApp, $this->output);

            // 2b. - permission reset: seemed to stop composer install?
            $this->resetAppFilePermissions($cloudwaysApp, $this->output);

            // 3. composer install
            // 4. run migrations
            $remoteSsh = new RemoteSSH($cloudwaysApp);
            $remoteSsh
                ->setOutput($this->getOutput())
                ->cdToApplication()
                ->composerInstallNoDev($discard)
                ->laravelArtisanMigrate();

            CloudwaysDeployLaravelPrependRemoteSsh::dispatch($cloudwaysApp, $remoteSsh);

            // 5. build cache for config, routes and views:
            if ($cache) {
                $remoteSsh->laravelArtisanCache();
            } else {
                $remoteSsh->laravelArtisanClear();
            }

            $this->outputSshProcess($remoteSsh->runCommands());
        }

    }

    protected function outputSshProcess($process_or_string): void
    {
        if (is_string($process_or_string)) {
            $this->info($process_or_string);
        } elseif ($process_or_string instanceof Process) {
            //dd($process);
            if ($process_or_string->isSuccessful()) {
                $this->info('remote ssh has ran');
            } else {
                $this->comment($process_or_string->getExitCode());
                $this->comment($process_or_string->getExitCodeText());
                $this->error('remote ssh has failed!');
                // dd(__METHOD__);
            }
            $this->comment($process_or_string->getOutput());
        } else {
            // dd($process_or_string);
        }
    }
}
