<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rule_notification_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_channel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alert_rule_id', 'notification_channel_id'], 'alert_rule_notification_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rule_notification_channel');
    }
};
