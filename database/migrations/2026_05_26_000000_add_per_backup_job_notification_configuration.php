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
            $table->boolean('notifications_enabled')->default(true)->after('status')->index();
        });

        Schema::table('notification_channels', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active')->index();
        });

        $defaultChannelId = DB::table('notification_channels')->orderBy('id')->value('id');

        if (! $defaultChannelId) {
            return;
        }

        DB::table('notification_channels')
            ->where('id', $defaultChannelId)
            ->update(['is_default' => true]);

        $now = now();
        $rows = DB::table('backup_jobs')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (int $jobId): array => [
                'backup_job_id' => $jobId,
                'notification_channel_id' => $defaultChannelId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('backup_job_notification_channel')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropIndex(['is_default']);
            $table->dropColumn('is_default');
        });

        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropIndex(['notifications_enabled']);
            $table->dropColumn('notifications_enabled');
        });
    }
};
