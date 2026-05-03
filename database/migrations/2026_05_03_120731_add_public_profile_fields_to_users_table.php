<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_slug')->unique()->nullable()->after('leaderboard_opt_in');
            $table->boolean('public_profile_enabled')->default(false)->after('public_slug');
            $table->json('public_visible_fields')->nullable()->after('public_profile_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['public_slug', 'public_profile_enabled', 'public_visible_fields']);
        });
    }
};
