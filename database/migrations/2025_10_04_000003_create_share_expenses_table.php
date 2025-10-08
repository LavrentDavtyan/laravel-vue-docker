<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('share_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('topic_id')
                  ->constrained('share_topics')
                  ->cascadeOnDelete();

            // ✅ payer is a user, not a member row
            $table->foreignId('payer_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('description');
            // ✅ single amount column we’ll use everywhere
            $table->decimal('amount', 12, 2);

            // ✅ currency we store on the expense (defaults to topic currency on create)
            $table->string('currency', 3)->default('USD');

            // (optional, legacy-friendly)
            $table->date('date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['topic_id', 'payer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_expenses');
    }
};
