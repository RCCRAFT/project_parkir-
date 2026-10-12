<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    protected FonnteService $fonnte;

    public function __construct(FonnteService $fonnte)
    {
        $this->fonnte = $fonnte;
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    // Cari user berdasarkan email, lalu cocokkan nomor dari kolom nomor_hp
    // (kedua sisi dinormalisasi jadi format 62xxx)
    private function findUser(string $email, string $phone): ?User
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();

        if (!$user) {
            return null;
        }

        return $this->normalizePhone($user->nomor_hp) === $phone ? $user : null;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);

        $phone = $this->normalizePhone($request->phone);

        $user = $this->findUser($request->email, $phone);

        if (!$user) {
            return response()->json([
                'message' => 'Email dan nomor telepon tidak cocok dengan data kami',
            ], 404);
        }

        $recentCount = OtpCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan, coba lagi dalam beberapa menit',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        OtpCode::create([
            'phone' => $phone,
            'code' => $otp,
            'expired_at' => now()->addMinutes(5),
        ]);

        $result = $this->fonnte->sendMessage(
            $phone,
            "Kode OTP reset password kamu: {$otp}\nBerlaku 5 menit. Jangan bagikan kode ini ke siapapun."
        );

        if (isset($result['status']) && $result['status'] === false) {
            return response()->json(['message' => 'Gagal mengirim OTP, coba lagi'], 500);
        }

        return response()->json(['message' => 'Kode OTP berhasil dikirim ke WhatsApp']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        $phone = $this->normalizePhone($request->phone);

        if (!$this->findUser($request->email, $phone)) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $otpRow = OtpCode::where('phone', $phone)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otpRow || $otpRow->expired_at->isPast()) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
        }

        // sengaja belum ditandai "used" di sini, baru ditandai saat
        // password berhasil diganti di resetPassword

        return response()->json(['message' => 'Kode OTP berhasil diverifikasi']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
            'otp' => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $phone = $this->normalizePhone($request->phone);

        $otpRow = OtpCode::where('phone', $phone)
            ->where('code', $request->otp)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otpRow || $otpRow->expired_at->isPast()) {
            return response()->json(['message' => 'Sesi OTP tidak valid, ulangi dari awal.'], 400);
        }

        $user = $this->findUser($request->email, $phone);

        if (!$user) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $otpRow->update(['is_used' => true]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }
}