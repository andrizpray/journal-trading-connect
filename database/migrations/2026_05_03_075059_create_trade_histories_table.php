<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();
            $table->string('ticket', 30)->index();
            $table->date('open_date');
            $table->date('close_date')->nullable();
            $table->string('currency_pair', 20);
            $table->enum('trade_type', ['buy', 'sell', 'buy_limit', 'sell_limit', 'buy_stop', 'sell_stop'])->default('buy');
            $table->decimal('lot_size', 12, 2)->default(0);
            $table->decimal('open_price', 20, 8)->nullable();
            $table->decimal('close_price', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->decimal('swap', 20, 2)->default(0);
            $table->decimal('commission', 20, 2)->default(0);
            $table->decimal('profit_loss', 20, 2)->default(0);
            $table->string('result', 10)->nullable(); // win, loss, break_even
            $table->integer('duration_minutes')->nullable(); // durasi trade dalam menit
            $table->string('comment')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'trading_account_id', 'ticket']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_histories');
    }
};
