<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tagihan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScanController extends Controller
{
    // Generate tagihan otomatis setiap tanggal 1
    public function generateTagihanBulanan()
    {
        // Jalankan via cron / scheduler Laravel
        if (Carbon::now()->day != 1) {
            return response()->json(['message' => 'Hanya bisa dijalankan tanggal 1']);
        }

        $members = Member::where('status', 'aktif')->get();
        $bulan = Carbon::now()->format('Y-m');

        foreach ($members as $member) {
            // Cek apakah sudah ada tagihan bulan ini
            $exists = Tagihan::where('member_id', $member->id)
                ->where('periode', $bulan)
                ->exists();

            if (!$exists) {
                Tagihan::create([
                    'member_id'   => $member->id,
                    'periode'     => $bulan,
                    'jumlah'      => $member->biaya_bulanan ?? 50000, // sesuaikan
                    'status'      => 'belum_lunas',
                    'jatuh_tempo' => Carbon::now()->endOfMonth(),
                ]);
            }
        }

        return response()->json(['message' => 'Tagihan bulan ini berhasil digenerate']);
    }

    // Proses pembayaran
    public function bayar(Request $request)
    {
        $request->validate([
            'tagihan_id' => 'required|exists:tagihans,id',
            'metode'     => 'required|in:tunai,transfer,qris',
            'jumlah'     => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $tagihan = Tagihan::findOrFail($request->tagihan_id);

            if ($tagihan->status === 'lunas') {
                return response()->json(['message' => 'Tagihan sudah lunas'], 400);
            }

            $transaksi = Transaksi::create([
                'tagihan_id'   => $tagihan->id,
                'member_id'    => $tagihan->member_id,
                'jumlah'       => $request->jumlah,
                'metode'       => $request->metode,
                'status'       => 'berhasil',
                'dibayar_oleh' => auth()->id(),
            ]);

            $tagihan->update([
                'status'      => 'lunas',
                'tanggal_bayar' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'   => 'Pembayaran berhasil',
                'transaksi' => $transaksi,
                'tagihan'   => $tagihan->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}