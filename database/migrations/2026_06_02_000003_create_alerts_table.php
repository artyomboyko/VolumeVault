<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('status')->index();
            $table->string('severity')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->unsignedInteger('trigger_count')->default(0);
            $table->timestamp('first_triggered_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['alert_rule_id', 'subject_type', 'subject_id'], 'alert_rule_subject_unique');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
