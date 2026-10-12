<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'petugas') {
            $status = strtolower(trim($user->status ?? 'aktif'));

            if (!in_array($status, ['aktif', 'active', '1'])) {
                $user->tokens()->delete();

                return response()->json([
                    'message' => 'Akun kamu nonaktif. Hubungi admin untuk mengaktifkan kembali.',
                ], 401);
            }
        }

        return $next($request);
    }
}