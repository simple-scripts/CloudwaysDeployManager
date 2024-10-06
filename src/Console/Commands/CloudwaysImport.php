<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Models\CloudwaysServer;

class CloudwaysImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CloudServers from database/JSON/servers.json file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $json_path = base_path('database/JSON/servers.json');
        if (! File::exists($json_path)) {
            $this->error('File does not exist: '.$json_path);

            return static::FAILURE;
        }

        $json = File::json($json_path, true);
        foreach ($json as $item) {
            $data = $item;
            unset($data['apps']);
            $cloudwaysServer = CloudwaysServer::query()->updateOrCreate(['id' => $data['id']], $data);

            if ($cloudwaysServer->exists) {
                $this->comment($cloudwaysServer->name.' server has been saved to the database with ID: '.$cloudwaysServer->id);

                // now the apps:
                foreach ($item['apps'] as $app) {
                    /** @var CloudwaysApp $cloudApp */
                    $cloudApp = CloudwaysApp::query()->updateOrCreate(['id' => $app['id']], $app);

                    if ($cloudApp) {
                        $this->comment('UpdateOrCreate: '.$cloudApp->name.' successfully! Short code: '.$cloudApp->short_code.
                            '  with ID: '.$cloudApp->id
                        );
                    }
                }
            }
        }

        $this->output->success('All CloudApps have been saved from JSON file');

        return static::SUCCESS;
    }
}
