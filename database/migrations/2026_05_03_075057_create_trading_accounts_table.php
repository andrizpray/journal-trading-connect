<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_number', 20);
            $table->string('account_name')->nullable();
            $table->string('broker');
            $table->enum('platform', ['mt4', 'mt5', 'other'])->default('mt5');
            $table->enum('sync_method', ['csv', 'metaapi', 'broker_api'])->default('csv');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('total_trades')->default(0);
            $table->decimal('total_pnl', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_accounts');
    }
};
