<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cloudways_apps', function (Blueprint $table) {
            $table->id()->comment('The ID as defined for this Cloudways App in Cloudways');
            $table->string('short_code')->nullable()->comment('Easy to remember short code to select app for command line calls')->unique();
            $table->foreignId('cloudways_server_id')->comment('Numeric id of the Cloudways server and FK = cloudways_servers.id');
            $table->string('type')->default('dev')->comment('local, dev, stage or prod');
            $table->string('group')->nullable()->comment('A way to group your apps together ex: marketing-sites');
            $table->string('name')->comment('Cloudways application name');
            $table->string('username')->comment('Cloudways application username');
            $table->string('path')->nullable()->comment('Cloudways application path, ex: applications/vhqbywfsvs/public_html');
            $table->string('url')->nullable()->comment('Cloudways application url');
            $table->bigInteger('cred_id')->nullable()->comment('Generated App user cred id from Cloudways API calls');
            $table->string('git_url')->nullable()->comment('Cloudways application git url');
            $table->string('git_branch')->nullable()->comment('Cloudways application git branch that will be used for deployment');
            $table->string('deploy_path')->nullable()->comment('Directory where the repo will be deployed. Leave the field empty for deploying in the standard public_html folder');

            $table->timestamps();
            $table->index(['type', 'group'], 'idx_cw_app_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudways_apps', function (Blueprint $table) {
            //
        });
    }
};
