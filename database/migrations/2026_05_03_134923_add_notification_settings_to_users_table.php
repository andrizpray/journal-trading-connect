<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_daily_journal')->default(false)->after('public_visible_fields');
            $table->boolean('notify_weekly_review')->default(false)->after('notify_daily_journal');
            $table->boolean('notify_trade_result')->default(false)->after('notify_weekly_review');
            $table->time('daily_journal_time')->default('20:00')->after('notify_trade_result');
            $table->string('weekly_review_day')->default('sunday')->after('daily_journal_time');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_daily_journal',
                'notify_weekly_review',
                'notify_trade_result',
                'daily_journal_time',
                'weekly_review_day',
            ]);
        });
    }
};
