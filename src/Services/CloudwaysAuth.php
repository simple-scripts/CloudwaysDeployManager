<?php

namespace SimpleScripts\CloudDeployManager\Services;

use Rockbuzz\LaraCwApi\Auth;

class CloudwaysAuth
{
    public static function getCloudwaysREST(): CloudwaysREST
    {
        $cloudwaysAuth = new Auth(config('cloudways.email'), config('cloudways.api_key'));

        return new CloudwaysREST($cloudwaysAuth);
    }
}
