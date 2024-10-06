<?php

namespace SimpleScripts\CloudDeployManager\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SimpleScripts\CloudDeployManager\Models\CloudwaysApp;
use SimpleScripts\CloudDeployManager\Services\RemoteSSH;

class CloudwaysDeployLaravelPrependRemoteSsh
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CloudwaysApp $cloudwaysApp;

    public RemoteSSH $remoteSsh;

    /**
     * Create a new event instance.
     */
    public function __construct(CloudwaysApp $cloudwaysApp, RemoteSsh $remoteSsh)
    {
        $this->cloudwaysApp = $cloudwaysApp;
        $this->remoteSsh = $remoteSsh;
    }
}
