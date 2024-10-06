<?php

namespace SimpleScripts\CloudDeployManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SimpleScripts\CloudDeployManager\CloudDeployManager
 */
class CloudDeployManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SimpleScripts\CloudDeployManager\CloudDeployManager::class;
    }
}
