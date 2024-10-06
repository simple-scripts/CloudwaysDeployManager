<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Models\CloudwaysServer;

class CloudwaysExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export saved Cloudways Servers and Apps to JSON file, excluding local ';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = CloudwaysServer::query();

        $serverCollection = $builder->get()->all();

        $data = [];

        /** @var CloudwaysServer $cloudwaysServer */
        foreach ($serverCollection as $cloudwaysServer) {
            $this->comment('Getting info for server: '.$cloudwaysServer->id.' - '.$cloudwaysServer->name);

            $item = $cloudwaysServer->toArray();

            // Remove the fields that are unique per install/user:
            unset($item['ssh_key_id'], $item['cloudways_app_cred_id']);

            $item['apps'] = [];
            /** @var CloudwaysApp $cloudwaysApp */
            foreach ($cloudwaysServer->apps as $cloudwaysApp) {
                // Now the apps:
                $this->comment(
                    ' - including app '.$cloudwaysApp->short_code.' ('.$cloudwaysApp->id.') '.$cloudwaysApp->name
                );
                $app_data = $cloudwaysApp->toArray();
                unset($app_data['cred_id']);
                $item['apps'][] = $app_data;
            }

            $data[] = $item;
        }

        File::ensureDirectoryExists(base_path('database/JSON/'));
        File::put(base_path('database/JSON/servers.json'), json_encode($data));

        $this->output->success('File has been created at: '.base_path('database/JSON/servers.json'));
    }
}
