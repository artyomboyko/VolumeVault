<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'default_per_page')) {
            Schema::table('users', function (Blueprint $table) {
                $table->smallInteger('default_per_page')->default(10)->after('theme');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'default_per_page')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('default_per_page');
            });
        }
    }
};
