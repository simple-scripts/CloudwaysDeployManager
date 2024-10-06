<?php

namespace SimpleScripts\CloudDeployManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id The ID as defined for this Cloudways App in Cloudways
 * @property int cloudways_server_id - Numeric id of the Cloudways server and FK = cloudways_servers.id
 * @property string $short_code - Easy to remember short code to select app for command line calls - unique
 * @property string $type - local, dev, stage or prod
 * @property string group - A way to group your apps together ex: marketing-sites
 * @property string name - Cloudways application name
 * @property string username - Cloudways application username
 * @property string path - Cloudways application path, ex: applications/vhqbywfsvs/public_html
 * @property string url - Cloudways application url
 * @property int $cred_id - Generated App user cred id from Cloudways API calls
 * @property string git_url - Cloudways application git url
 * @property string git_branch - Cloudways application git branch that will be used for deployment
 * @property string deploy_path - Directory where the repo will be deployed. Leave the field empty for deploying in the standard public_html folder
 * @property CloudwaysServer $server
 */
class CloudwaysApp extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the server that owns the app
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(CloudwaysServer::class);
    }
}
