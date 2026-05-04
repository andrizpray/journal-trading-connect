<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->string('api_token_hash', 64)->nullable()->unique()->after('api_token');
        });

        DB::table('trading_accounts')
            ->whereNotNull('api_token')
            ->orderBy('id')
            ->chunkById(200, function ($accounts): void {
                foreach ($accounts as $account) {
                    DB::table('trading_accounts')
                        ->where('id', $account->id)
                        ->update([
                            'api_token_hash' => hash('sha256', $account->api_token),
                            'api_token' => null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->dropUnique(['api_token_hash']);
            $table->dropColumn('api_token_hash');
        });
    }
};
