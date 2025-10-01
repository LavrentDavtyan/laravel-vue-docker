<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('category', 100)->index();
            // Month anchor (YYYY-MM-01) stored as date
            $table->date('month')->index();
            $table->decimal('amount_decimal', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['user_id', 'category', 'month']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('budgets');
    }
};
