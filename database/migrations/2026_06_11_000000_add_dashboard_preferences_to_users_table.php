<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'dashboard_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('dashboard_preferences')->nullable()->after('default_per_page');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'dashboard_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dashboard_preferences');
            });
        }
    }
};
