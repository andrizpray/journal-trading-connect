<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_history_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency_pair', 20);
            $table->enum('trade_type', ['buy', 'sell'])->default('buy');
            $table->decimal('profit_loss', 20, 2)->default(0);
            $table->enum('result', ['win', 'loss', 'break_even'])->nullable();
            $table->text('analysis')->nullable();
            $table->text('lesson_learned')->nullable();
            $table->integer('emotion_score')->nullable(); // 1-5
            $table->string('market_condition')->nullable(); // trending, ranging, volatile, calm
            $table->string('strategy_used')->nullable();
            $table->boolean('auto_imported')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
