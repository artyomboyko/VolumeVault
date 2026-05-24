<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->text('backup_key')->nullable()->after('docker_container_id');
            $table->unsignedBigInteger('backup_size_bytes')->nullable()->after('backup_key');
        });
    }

    public function down(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->dropColumn(['backup_key', 'backup_size_bytes']);
        });
    }
};
