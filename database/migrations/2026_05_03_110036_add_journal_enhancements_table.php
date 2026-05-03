<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // 3.2 — Screenshot attachment
            $table->string('screenshot_path')->nullable()->after('auto_imported');

            // 3.3 — Tags (comma-separated)
            $table->string('tags')->nullable()->after('screenshot_path');

            // 3.4 — Template type (pre_trade, post_trade, weekly_review)
            $table->string('template_type')->nullable()->after('tags');

            // 3.4 — Trading plan fields
            $table->string('plan_setup')->nullable()->after('template_type');
            $table->string('plan_entry')->nullable()->after('plan_setup');
            $table->decimal('plan_sl', 20, 8)->nullable()->after('plan_entry');
            $table->decimal('plan_tp', 20, 8)->nullable()->after('plan_sl');
            $table->text('plan_reasoning')->nullable()->after('plan_tp');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn([
                'screenshot_path',
                'tags',
                'template_type',
                'plan_setup',
                'plan_entry',
                'plan_sl',
                'plan_tp',
                'plan_reasoning',
            ]);
        });
    }
};
