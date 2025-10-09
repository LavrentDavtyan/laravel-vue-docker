<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('share_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('share_topics')->cascadeOnDelete();
            $table->foreignId('from_member_id')->constrained('share_topic_members')->cascadeOnDelete();
            $table->foreignId('to_member_id')->constrained('share_topic_members')->cascadeOnDelete();
            $table->decimal('amount_decimal', 14, 2);
            $table->string('note')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['topic_id', 'from_member_id', 'to_member_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('share_settlements');
    }
};
