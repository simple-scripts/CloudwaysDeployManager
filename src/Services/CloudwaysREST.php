<?php

namespace SimpleScripts\CloudDeployManager\Services;

use Illuminate\Console\OutputStyle;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Rockbuzz\LaraCwApi\Cloudways;

class CloudwaysREST extends Cloudways
{
    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getOperationStatus(int $operation_id): array
    {
        return $this->startHttpRequest()
            ->get(
                '/operation/'.$operation_id,
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function resetAppFilePermissions(int $server, int $app)
    {
        //'/server/manage/settings?server_id='.$server_id ,
        return $this->startHttpRequest()
            ->post(
                // /v1/app/manage/reset_permissions
                '/app/manage/reset_permissions',
                [
                    'server_id' => $server,
                    'app_id' => $app,
                ]
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function createAppCredentials(int $server, int $app, string $username, ?string $password = null)
    {
        if (empty($password)) {
            $password = Str::password(18);
        }

        //dd($server, $app, $username, $password);
        // https://developers.cloudways.com/docs/#!/AppManagementApi#createAppCredentials
        return $this->startHttpRequest()
            ->post(
                // //api/v1/app/creds
                '/app/creds',
                [
                    'server_id' => $server,
                    'app_id' => $app,
                    'username' => $username,
                    'password' => $password,
                ]
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function createMySshKey(int $server, int $app_creds_id)
    {
        $public_key = config('cw-deploy-manager.ssh.public_key') ?? file_get_contents(config('cw-deploy-manager.ssh.public_key_path'));

        return $this->startHttpRequest()
            ->post(
                // /api/v1/ssh_key
                // /api/v2/ssh_key
                '/ssh_key',
                [
                    'server_id' => $server,
                    'ssh_key_name' => config('cw-deploy-manager.ssh.key_uploaded_name'), //String Form True Label for SSH key
                    'ssh_key' => $public_key, // String Form True SSH Key
                    'app_creds_id' => $app_creds_id, // Integer Form True - Numeric id of the App Credentials (required for app level ssh keys)
                ]
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function updateMySshKey(int $server, int $ssh_key_id)
    {
        // @see https://developers.cloudways.com/docs/#!/SSHKeysManagementApi#createSSHkey
        $token = $this->auth->getOAuthAccessToken();

        //'/server/manage/settings?server_id='.$server_id ,
        return Http::cloudways()
            ->withToken($token->value)
            ->put(
                // /api/v1/ssh_key
                '/ssh_key/'.$ssh_key_id,
                [
                    'server_id' => $server,
                    'ssh_key_name' => config('cw-deploy-manager.ssh.key_uploaded_name'), //String Form True Label for SSH key
                    'ssh_key_id' => $ssh_key_id,
                    // 'ssh_key' => file_get_contents(config('public_key_path')),// String Form True SSH Key
                    // app_creds_id	Integer	Form	True
                    //Numeric id of the App Credentials (required for app level ssh keys)
                ]
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function waitForOperationStatusCompletion(int $operation_id, ?OutputStyle $output): void
    {
        $json = $this->getOperationStatus($operation_id);

        if (array_key_exists('operation', $json)) {
            //dd($json);
            $status = $json['operation'];
            if ($status['is_completed']) {
                if (isset($status['status'])) {
                    $output->success($status['type'].' status:  '.$status['status'].' with operation ID: '.$status['id']);
                } else {
                    $output->success($status['type'].' completed with operation ID: '.$status['id']);
                }

            } else {
                $time = $status['estimated_time_remaining'] > 0 ? $status['estimated_time_remaining'] : 10;
                $output->writeln('Operation ID: '.$status['id'].' for '.$status['type'].
                    ' has estimated time remaining of: '.$time.' seconds. ');

                //print_r($status);exit();
                sleep($time);
                $this->waitForOperationStatusCompletion($operation_id, $output);
            }
        } else {
            // dd($json);
        }
    }

    /**
     * @throws RequestException
     */
    public function getServerList(): array
    {
        $token = $this->auth->getOAuthAccessToken();

        return Http::cloudways()
            ->withToken($token->value)
            ->get(
                '/server',
            )
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function getServerSettings($server_id)
    {
        $token = $this->auth->getOAuthAccessToken();

        $ret = Http::cloudways()
            ->withToken($token->value)
            ->get(
                '/server/manage/settings?server_id='.$server_id,
            )
            ->throw()
            ->json();

        return $ret;
    }

    protected function startHttpRequest(): Http|PendingRequest
    {
        // @see https://developers.cloudways.com/docs/#!/SSHKeysManagementApi#createSSHkey
        $token = $this->auth->getOAuthAccessToken();

        return Http::cloudways()
            ->withOptions(['timeout' => config('cw-deploy-manager.timeout')])
            ->withToken($token->value);
    }
}
