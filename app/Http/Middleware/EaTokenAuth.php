<?php

namespace App\Http\Middleware;

use App\Models\TradingAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EaTokenAuth
{
    /**
     * Validasi request dari EA menggunakan Bearer token.
     * Token = api_token dari trading_accounts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token required',
                'message' => 'Kirim header Authorization: Bearer {token}',
            ], 401);
        }

        $account = TradingAccount::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => 'Token tidak valid atau akun tidak aktif',
            ], 401);
        }

        // Simpan account ke request agar bisa diakses di controller
        $request->attributes->set('ea_account', $account);

        return $next($request);
    }
}
