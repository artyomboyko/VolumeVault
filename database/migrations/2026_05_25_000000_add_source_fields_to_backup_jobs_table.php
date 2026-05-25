<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->string('source_type')->default('docker_volume')->after('name')->index();
            $table->text('host_path')->nullable()->after('volume_name');
            $table->string('volume_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('backup_jobs')->whereNull('volume_name')->update(['volume_name' => '']);

        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->string('volume_name')->nullable(false)->change();
            $table->dropColumn(['source_type', 'host_path']);
        });
    }
};
