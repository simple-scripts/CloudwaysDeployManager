<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\Command;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;

class CloudwaysListCommand extends Command
{
    use CloudwaysDeployGitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cw:manager:list
        {--g|group= : As defined in the CloudApp->group column }
        {--t|type= : local, dev, stage or prod }
        {--s|short_codes= : Limit to a comma seperated list of App\Models\CloudApp short codes to run }
        {--i|ids= : Limit to a comma seperated list of App\Models\CloudApp IDs to run }
        {--c|columns=url : Columns to show, server or url }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Cloudways Apps and servers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $columns = strtolower($this->option('columns'));
        $headers = ['Group', 'Type', 'Short Code', 'Name', 'App ID'];

        switch ($columns) {
            case 'server':
                $headers = array_merge($headers, ['Server', 'Server ID']);
                break;
            case 'url':
                $headers = array_merge($headers, ['URL']);
                break;
        }
        $rows = [];

        $appCollection = $this->getCloudwaysAppCollectionFromCliInput();

        /** @var CloudwaysApp $cloudwaysApp */
        foreach ($appCollection as $cloudwaysApp) {
            $row = [
                'Group' => $cloudwaysApp->group,
                'Type' => $cloudwaysApp->type,
                'Short Code' => $cloudwaysApp->short_code,
                'Name' => $cloudwaysApp->name,
                'App ID' => $cloudwaysApp->id,
            ];
            switch ($columns) {
                case 'server':
                    $row = array_merge($row, [
                        'Server' => $cloudwaysApp->server->name,
                        'Server ID' => $cloudwaysApp->server->id,
                    ]);
                    break;
                case 'url':
                    $row = array_merge($row, [
                        'URL' => $cloudwaysApp->url,
                    ]);
                    break;
            }

            $rows[] = $row;
        }

        $this->comment('** Cloudways Apps **');
        $this->output->table($headers, $rows);
    }
}
