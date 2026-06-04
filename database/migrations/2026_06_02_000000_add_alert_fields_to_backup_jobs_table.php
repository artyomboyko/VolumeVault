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
            $table->boolean('use_custom_alert_settings')->default(false)->after('notifications_enabled');
            $table->boolean('alert_notifications_enabled')->default(true)->after('use_custom_alert_settings');
            $table->timestamp('last_error_at')->nullable()->after('last_error')->index();
        });

        DB::table('backup_jobs')
            ->where('status', 'error')
            ->whereNull('last_error_at')
            ->update(['last_error_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropIndex(['last_error_at']);
            $table->dropColumn([
                'use_custom_alert_settings',
                'alert_notifications_enabled',
                'last_error_at',
            ]);
        });
    }
};
