<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('share_join_requests', function (Blueprint $table) {
            $table->id();

            // Request scope
            $table->unsignedBigInteger('topic_id');
            $table->unsignedBigInteger('requester_user_id');

            // pending | approved | denied
            $table->string('status', 20)->default('pending')->index();

            // Optional: request context
            $table->string('invite_token', 255)->nullable()->index();
            $table->text('message')->nullable();

            // Optional: moderation (who decided & when)
            $table->unsignedBigInteger('decided_by_user_id')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            // FKs
            $table->foreign('topic_id')
                  ->references('id')->on('share_topics')
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('requester_user_id')
                  ->references('id')->on('users')
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('decided_by_user_id')
                  ->references('id')->on('users')
                  ->onUpdate('cascade')->onDelete('set null');

            // Make duplicates hard: one pending per user/topic
            $table->unique(['topic_id', 'requester_user_id', 'status'], 'uq_topic_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_join_requests');
    }
};
