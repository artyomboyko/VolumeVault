<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_alert_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['backup_job_id', 'alert_rule_id'], 'job_alert_config_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_alert_configs');
    }
};
