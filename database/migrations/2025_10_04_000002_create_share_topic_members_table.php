<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('share_topic_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('share_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // guest mode later
            $table->string('display_name');
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->index(['topic_id', 'user_id']);
            // We cannot strictly unique(topic_id, user_id) with nullable user_id across DBs; enforce in code later.
        });
    }

    public function down(): void {
        Schema::dropIfExists('share_topic_members');
    }
};
