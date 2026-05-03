<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->unsignedTinyInteger('decimal_places_fx')->default(5)->after('currency');
            $table->unsignedTinyInteger('decimal_places_jpy')->default(3)->after('decimal_places_fx');
            $table->unsignedTinyInteger('decimal_places_metal')->default(2)->after('decimal_places_jpy');
        });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'decimal_places_fx',
                'decimal_places_jpy',
                'decimal_places_metal',
            ]);
        });
    }
};
