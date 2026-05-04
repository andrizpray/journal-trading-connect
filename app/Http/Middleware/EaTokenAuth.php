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
     * Token divalidasi melalui hash SHA-256 di trading_accounts.
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

        $tokenHash = hash('sha256', $token);

        $account = TradingAccount::where('api_token_hash', $tokenHash)
            ->where('is_active', true)
            ->first();

        // Backward compatibility: migrate legacy plaintext token on first valid request.
        if (!$account) {
            $account = TradingAccount::where('api_token', $token)
                ->where('is_active', true)
                ->first();

            if ($account) {
                $account->forceFill([
                    'api_token_hash' => $tokenHash,
                    'api_token' => null,
                ])->saveQuietly();
            }
        }

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
