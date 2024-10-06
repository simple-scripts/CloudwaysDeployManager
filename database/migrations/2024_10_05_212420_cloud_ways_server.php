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
        Schema::table('cloudways_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Server name as written in Cloudways');
            $table->string('ip_address')->nullable()->comment('IP address of the Cloudways server');
            $table->string('username')->nullable()->comment('Server username as written in Cloudways');
            $table->string('password')->nullable()->comment('Server password as written in Cloudways');
            $table->bigInteger('ssh_key_id')->nullable()->comment('Server SSH Key id set from Cloudways API calls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudways_servers', function (Blueprint $table) {
            //
        });
    }
};
