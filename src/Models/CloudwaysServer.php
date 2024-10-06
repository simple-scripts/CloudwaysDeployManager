<?php

namespace SimpleScripts\CloudDeployManager\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ip_address - IP address of the Cloudways server
 * @property string $name - Server name as written in Cloudways
 * @property string $username - Server username as written in Cloudways
 * @property string $password - Server username password as written in Cloudways
 * @property int $ssh_key_id - Server SSH Key id set from Cloudways API calls
 * @property Collection $apps
 */
class CloudwaysServer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get apps associated with the server
     */
    public function apps(): HasMany
    {
        return $this->hasMany(CloudwaysApp::class);
    }
}
