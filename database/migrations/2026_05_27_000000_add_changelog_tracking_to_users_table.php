<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_seen_app_version')) {
                $table->string('last_seen_app_version')->nullable()->after('theme');
            }

            if (! Schema::hasColumn('users', 'last_seen_changelog_id')) {
                $table->string('last_seen_changelog_id')->nullable()->after('last_seen_app_version');
            }

            if (! Schema::hasColumn('users', 'last_dismissed_available_version')) {
                $table->string('last_dismissed_available_version')->nullable()->after('last_seen_changelog_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_seen_changelog_id')) {
                $table->dropColumn('last_seen_changelog_id');
            }

            if (Schema::hasColumn('users', 'last_dismissed_available_version')) {
                $table->dropColumn('last_dismissed_available_version');
            }

            if (Schema::hasColumn('users', 'last_seen_app_version')) {
                $table->dropColumn('last_seen_app_version');
            }
        });
    }
};
