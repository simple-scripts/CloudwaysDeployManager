<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use SimpleScripts\CloudDeployManager\Events\CloudwaysDeployLaravelPrependRemoteSsh;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\CloudwaysAuth;
use SimpleScripts\CloudDeployManager\Services\RemoteSSH;
use Symfony\Component\Process\Process;

class CloudwaysServerRestartCommand extends Command
{
    use CloudwaysDeployGitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:server
        {--a|action=restart : Action: restart, start or stop }
        {--g|group= : As defined in the CloudApp->group column }
        {--t|type=dev : local, dev, stage or prod }
        {--s|short_codes= : Limit to a comma separated list of App\Models\CloudApp short codes to run }
        {--i|ids= : Limit to a comma separated list of App\Models\CloudApp IDs to run }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Select a Cloudways Server by the App or group and restart, start or stop it.';

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
        $action = $this->option('action');

        $appCollection = $this->getCloudwaysAppCollectionFromCliInput();

        $groupedByServer = $appCollection->groupBy('cloudways_server_id');

        $cloudwaysRest = CloudwaysAuth::getCloudwaysREST();

        foreach ($groupedByServer as $serverId => $appsForServer) {
            /** @var CloudwaysApp $firstApp */
            $firstApp = $appsForServer->first();
            $this->info('Server ID: ' . $serverId . ' - ' . $firstApp->server->name);
            $this->info('From selected apps:');
            /** @var CloudwaysApp $cloudwaysApp */
            foreach ($appsForServer as $cloudwaysApp) {
                $this->info(' - '.$cloudwaysApp->short_code.' ('.$cloudwaysApp->id.') '.$cloudwaysApp->name);
            }
            $this->info('Now attempting to '.$action.' server:');
            $operation_id = match ($action) {
                'restart' => $cloudwaysRest->serverRestart($serverId),
                'start' => $cloudwaysRest->serverStart($serverId),
                'stop' => $cloudwaysRest->serverStop($serverId),
                default => function() use ($action) {
                    $this->info('Unknown action: '.$action);
                    return false;
                },
            };

            if ($operation_id) {
                $cloudwaysRest->waitForOperationStatusCompletion($operation_id, $this->output, 15);

            } elseif ($this->output) {
                $this->error('Something went wrong!, git deploy did not work.');
            }
        }
    }
}
