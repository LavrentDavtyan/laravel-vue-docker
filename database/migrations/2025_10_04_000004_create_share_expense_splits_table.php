<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('share_expense_splits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('expense_id')
                  ->constrained('share_expenses')
                  ->cascadeOnDelete();

            $table->foreignId('member_id')
                  ->constrained('share_topic_members')
                  ->cascadeOnDelete();

            $table->decimal('share_amount', 12, 2);

            $table->timestamps();

            $table->index(['expense_id','member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_expense_splits');
    }
};
