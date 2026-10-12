<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:super_admin,petugas',
        ]);

        // Cek email dan password
        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // Ambil user yang berhasil login
        $user = Auth::user();

        // Ambil role dari database
        $role = strtolower(trim($user->role ?? ''));

        // Normalisasi role lama jika masih ada
        if (
            $role === 'super admin' ||
            $role === 'superadmin' ||
            $role === 'admin'
        ) {
            $role = 'super_admin';
        }

        if ($role === 'petugas parkir') {
            $role = 'petugas';
        }

        // Pastikan role di database valid
        if (!in_array($role, ['super_admin', 'petugas'])) {
            Auth::logout();

            return response()->json([
                'message' => 'Role akun tidak dikenali.'
            ], 403);
        }

        // Cek role yang dipilih di halaman login
        if ($role !== $credentials['role']) {
            Auth::logout();

            return response()->json([
                'message' => 'Jenis akun yang dipilih tidak sesuai dengan akun ini.'
            ], 403);
        }

        // ===== CEK STATUS AKUN (BARU) =====
        // Super admin dikecualikan supaya tidak bisa ke-lock out
        $status = strtolower(trim($user->status ?? 'aktif'));

        if (
            $role === 'petugas' &&
            !in_array($status, ['aktif', 'active', '1'])
        ) {
            Auth::logout();

            return response()->json([
                'message' => 'Akun kamu nonaktif. Hubungi admin untuk mengaktifkan kembali.'
            ], 403);
        }

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Tentukan halaman berdasarkan role
        $redirect = $role === 'super_admin' ? '/admin' : '/petugas';

        // Response berhasil
        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
            'redirect' => $redirect,
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        Auth::logout();

        return response()->json([
            'message' => 'Logout berhasil.'
        ], 200);
    }
}