<?php

namespace SimpleScripts\CloudDeployManager\Console\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\RequestException;
use Rockbuzz\LaraCwApi\Auth;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\CloudwaysREST;
use Symfony\Component\Console\Input\InputInterface;

trait CloudwaysDeployGitTrait
{
    protected ?string $group = null;

    protected ?string $type = null;

    protected array $ids = [];

    protected array $short_codes = [];

    public function validateEnv(): bool
    {
        if (empty(config('cloudways.email'))) {
            $this->error('Please define CLOUDWAYS_EMAIL in your .env file');

            return false;
        }
        if (empty(config('cloudways.api_key'))) {
            $this->error('Please define CLOUDWAYS_API_KEY in your .env file');

            return false;
        }

        return true;
    }

    public function getCloudwaysAppCollectionFromCliInput(bool $all = false): Collection
    {
        if (! $all && $this->input instanceof InputInterface) {
            if ($this->input->hasOption('group')) {
                $this->group = $this->input->getOption('group');
            }
            if ($this->input->hasOption('type')) {
                $this->type = $this->input->getOption('type');
            }
            if ($this->input->hasOption('short_codes')) {
                $tmp_short_codes = trim($this->input->getOption('short_codes'));
                if (! empty($tmp_short_codes)) {
                    $this->short_codes = array_map('trim', explode(',', $tmp_short_codes));
                }
            }
            if ($this->input->hasOption('ids')) {
                $tmp_ids = trim($this->input->getOption('ids'));
                if (! empty($tmp_ids)) {
                    $this->ids = array_map('trim', explode(',', $tmp_ids));
                }
            }
        }

        return $this->getCloudAppCollection($this->group, $this->type, $this->ids, $this->short_codes);
    }

    public function getCloudAppCollection(?string $group, ?string $type = 'dev', array $ids = [], array $short_codes = []): Collection
    {
        $group = $this->getGroupName($group);

        $this->output->text('Limit CloudwaysApp'.(count($ids) ? ' with IDs: '.implode(',', $ids).' ids' : '').
            (! empty($group) ? ' in '.$group.' group' : '').
            (! empty($type) ? ' with type '.$type : '').
            (count($short_codes) ? ' with a short code '.implode(',', $short_codes) : '')
        );

        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = CloudwaysApp::query();
        if (! empty($group)) {
            $builder->where('group', '=', $group);
        }
        if (! empty($type)) {
            $builder->where('type', '=', $type);
        }

        if (count($short_codes)) {
            $builder->whereIn('short_code', $short_codes);
        }

        if (count($ids)) {
            $builder->whereIn('id', $ids);
        }

        $builder
            ->orderBy('group')
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('short_code');

        return $builder->get();
    }

    /**
     * @return void - operation ID
     *
     * @throws RequestException
     */
    public function deployGitBranchToServer(CloudwaysApp $cloudwaysApp, ?OutputStyle $output): void
    {
        $cloudwaysAuth = new Auth(config('cloudways.email'), config('cloudways.api_key'));

        //        /** @var Cloudways $cloudways */
        //        $cloudways = app('cloudways');
        $cloudwaysRest = new CloudwaysREST($cloudwaysAuth);
        // @TODO cache the token for 30 minutes

        $operation_id = $cloudwaysRest->startGitPullLocal(
            $cloudwaysApp->cloudways_server_id,
            $cloudwaysApp->id,
            $cloudwaysApp->git_url,
            $cloudwaysApp->git_branch,
            $cloudwaysApp->deploy_path
        );

        if ($operation_id) {
            $cloudwaysRest->waitForOperationStatusCompletion($operation_id, $output);

        } elseif ($output) {
            $this->error('Something went wrong!, git deploy did not work.');
            exit();
        }

    }

    /**
     * @throws RequestException
     */
    public function resetAppFilePermissions(CloudwaysApp $cloudwaysApp, ?OutputStyle $output): void
    {
        $cloudwaysAuth = new Auth(config('cloudways.email'), config('cloudways.api_key'));

        //        /** @var Cloudways $cloudways */
        //        $cloudways = app('cloudways');
        $cloudwaysRest = new CloudwaysREST($cloudwaysAuth);

        $json = $cloudwaysRest->resetAppFilePermissions(
            $cloudwaysApp->cloudways_server_id,
            $cloudwaysApp->id
        );
        if (isset($json['operation_id'])) {
            $cloudwaysRest->waitForOperationStatusCompletion($json['operation_id'], $output);

        } elseif ($output) {
            $this->error('Something went wrong!, resetting app permission did not work.');
            exit();
        }
    }

    /**
     * @return mixed|string
     */
    protected function getGroupName($group): mixed
    {
        $group = strtolower($group);
        $tmp_group = config('cw-deploy-manager.group_alias.'.$group, false);
        if (! empty($tmp_group)) {
            return $tmp_group;
        }

        return $group;
    }
}
