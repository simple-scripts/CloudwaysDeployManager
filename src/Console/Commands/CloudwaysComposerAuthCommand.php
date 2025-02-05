<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\RemoteSSH;
use Symfony\Component\Process\Process;

class CloudwaysComposerAuthCommand extends Command
{
    use CloudwaysDeployGitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:composer-auth
        {--a|all=1 : Update all apps, set to 0 and use the other options }
        {--g|group= : As defined in the CloudApp->group column }
        {--t|type=dev : local, dev, stage or prod }
        {--s|short_codes= : Limit to a comma seperated list of App\Models\CloudApp short codes to run }
        {--i|ids= : Limit to a comma seperated list of App\Models\CloudApp IDs to run }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload/update the remote ~/.composer/auth.json file'.PHP_EOL.' - copy from: '.__DIR__.'/database/JSON/auth.json';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->output->comment('Begin ~/.composer/auth.json');

        $appCollection = $this->getCloudwaysAppCollectionFromCliInput((bool) $this->input->getOption('all'));

        $composer_auth = dirname(__DIR__, 3).'/database/JSON/auth.json';
        if (! file_exists($composer_auth)) {
            $this->error('Please define create a valid file in: '.$composer_auth);

            return;
        }
        // dd($composer_auth);
        /** @var CloudwaysApp $app */
        foreach ($appCollection as $app) {
            $this->info('--------'.PHP_EOL.'Starting ~/.composer/auth.json upload: '.$app->short_code.' - '.$app->id.' - '.
                $app->name.' on '.$app->url
            );

            $this->comment('You may need to enter in the server password: '.$app->server->password);
            // Upload the auth file:
            $remoteSsh = new RemoteSSH($app);
            $process_or_string = $remoteSsh
                ->uploadComposerAuthJson($composer_auth);

            if (is_string($process_or_string)) {
                $this->info($process_or_string);
            } elseif ($process_or_string instanceof Process) {
                // dd($process);
                if ($process_or_string->isSuccessful()) {
                    $this->info('remote upload file has ran');
                } else {
                    $this->comment($process_or_string->getExitCode());
                    $this->comment($process_or_string->getExitCodeText());
                    $this->error('remote upload file has failed!');
                    // dd(__METHOD__);
                }
                $this->comment($process_or_string->getOutput());
            } else {
                // dd($process_or_string);
            }
        }
    }
}
