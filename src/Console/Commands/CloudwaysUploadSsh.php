<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\CloudwaysAuth;
use SimpleScripts\CloudDeployManager\Services\RemoteSSH;

class CloudwaysUploadSsh extends Command
{
    use CloudwaysDeployGitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:ssh
        {--g|group= : As defined in the CloudApp->group column }
        {--t|type=dev : local, dev, stage or prod }
        {--s|short_codes= : Limit to a comma seperated list of App\Models\CloudApp short codes to run }
        {--i|ids= : Limit to a comma seperated list of App\Models\CloudApp IDs to run }
        {--new=0 : Add new SSH Key to existing Cloudways App Credential account }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload SSH Key as defined in the .env';

    /**
     * Execute the console command.
     *
     * @throws RequestException
     */
    public function handle(): void
    {
        if (empty(config('cw-deploy-manager.app_credentials_username'))) {
            $this->error('Please define CW_DM_APP_CREDENTIALS_USERNAME in your .env file');

            return;
        }
        if (empty(config('cw-deploy-manager.ssh.key_uploaded_name'))) {
            $this->error('Please define CW_DM_SSH_KEY_UPLOADED_NAME in your .env file');

            return;
        }
        // public_key_path
        if (empty(config('cw-deploy-manager.ssh.public_key')) && empty(config('cw-deploy-manager.ssh.public_key_path'))) {
            $this->error('Please define CW_DM_SSH_PUBLIC_KEY or CW_DM_SSH_PUBLIC_KEY_PATH in your .env file');

            return;
        }
        if (empty(config('cw-deploy-manager.ssh.private_key_path'))) {
            $this->error('Please define CW_DM_SSH_PRIVATE_KEY_PATH in your .env file');

            return;
        }

        $new = (bool) trim($this->input->getOption('new'));
        $appCollection = $this->getCloudwaysAppCollectionFromCliInput();

        $this->comment('Begin SSH Key create for '.(count($this->ids) ? implode(',', $this->ids).' ids' : 'all').' ');

        $confirm_ssh_commands = [];

        /** @var CloudwaysApp $cloudwaysApp */
        foreach ($appCollection as $cloudwaysApp) {
            $this->info('Starting SSH Key upload/create for server: '.$cloudwaysApp->short_code.' - '.$cloudwaysApp->id.' - '.$cloudwaysApp->server->name);

            $cloudwaysRest = CloudwaysAuth::getCloudwaysREST();

            if (empty($cloudwaysApp->cred_id)) {
                $response = $cloudwaysRest->createAppCredentials(
                    $cloudwaysApp->cloudways_server_id,
                    $cloudwaysApp->id,
                    config('cw-deploy-manager.app_credentials_username')
                );

                if ($response['status'] && $response['app_cred_id']) {
                    // Starting SSH Key upload/cre
                    // array:2 [ // app\Console\Co
                    //  "status" => true
                    //  "app_cred_id" => 1012039
                    // ]
                    $cloudwaysApp->update([
                        'cred_id' => $response['app_cred_id'],
                    ]);
                    $cloudwaysApp->refresh();
                    $this->info('New SSH auth account created ('.config('cw-deploy-manager.app_credentials_username').
                        ') for server: '.$cloudwaysApp->id.' - '.$cloudwaysApp->server->name.' for app: '.
                        $cloudwaysApp->id.' - '.$cloudwaysApp->name
                    );

                }
            }

            if (empty($cloudwaysApp->server->ssh_key_id) || $new) {
                $response = $cloudwaysRest->createMySshKey($cloudwaysApp->cloudways_server_id, $cloudwaysApp->cred_id);

                $cloudwaysApp->server->update([
                    'ssh_key_id' => $response['id'],
                ]);
                $this->info('SSH key created for user '.config('cw-deploy-manager.app_credentials_username').
                    ' on server: '.$cloudwaysApp->id.' - '.$cloudwaysApp->server->name.' for app: '.
                    $cloudwaysApp->id.' - '.$cloudwaysApp->name
                );
            }

            $remoteSSH = new RemoteSSH($cloudwaysApp);

            $confirm_ssh_commands[] = $remoteSSH->getSshCommandAsString(false);
            $this->info('SSH command: '.PHP_EOL.'  '.$remoteSSH->getSshCommandAsString(false));
        }

        File::ensureDirectoryExists(base_path('_ssh'));
        File::put(base_path('_ssh/confirm.sh'), implode(PHP_EOL, $confirm_ssh_commands));
    }
}
