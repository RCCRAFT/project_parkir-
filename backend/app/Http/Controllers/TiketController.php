<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use App\Models\Member;
use App\Models\MemberParkirLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TiketController extends Controller
{
    /**
     * ==========================================================
     * BERSIHKAN NOMOR PLAT
     * ==========================================================
     |
     | Rapikan plat (trim + huruf besar). Kosong atau "-" dianggap
     | belum diisi (null).
     |
     */
    private function bersihkanPlat($plat): ?string
    {
        $plat = strtoupper(trim((string) $plat));

        if ($plat === '' || $plat === '-') {
            return null;
        }

        return $plat;
    }

    /**
     * ==========================================================
     * BUAT TIKET BARU
     * NON-MEMBER / KENDARAAN MASUK
     * ==========================================================
     */
    public function create(Request $request)
    {
        $jenisKendaraan =
            $request->input('jenis_kendaraan')
            ?? $request->input('jenis');

        $nomorPolisi = $this->bersihkanPlat(
            $request->input('nomor_polisi')
            ?? $request->input('nomor_plat')
        );

        $last = TiketParkir::latest('id')->first();

        if ($last && $last->kode_tiket) {
            $angka = intval(substr($last->kode_tiket, 1));
            $nomor = $angka + 1;
        } else {
            $nomor = 1;
        }

        $kode = 'A' . str_pad($nomor, 3, '0', STR_PAD_LEFT);

        $tiket = TiketParkir::create([
            'kode_tiket' => $kode,
            'qr_code' => Str::random(60),
            'status' => 'masuk',
            'waktu_masuk' => now(),
            'waktu_keluar' => null,
            'jenis' => $jenisKendaraan,
            'nomor_polisi' => $nomorPolisi,
            'total_bayar' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuat.',
            'data' => $tiket,
        ], 201);
    }

    /**
     * ==========================================================
     * PROSES TIKET KELUAR
     * PEMBAYARAN NON-MEMBER
     * ==========================================================
     |
     | Nomor plat WAJIB ada saat keluar. Plat diambil dari input
     | petugas (nomor_polisi / nomor_plat), kalau kosong dipakai
     | plat yang sudah tersimpan di tiket.
     |
     */
    public function keluar(Request $request, $id)
    {
        $tiket = TiketParkir::findOrFail($id);

        $status = strtolower(trim((string) ($tiket->status ?? '')));

        if ($status === 'keluar' || !empty($tiket->waktu_keluar)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini sudah diproses dan kendaraan sudah keluar.',
                'data' => $tiket,
            ], 422);
        }

        $jenisKendaraan =
            $request->input('jenis_kendaraan')
            ?? $request->input('jenis')
            ?? $tiket->jenis;

        $nomorPolisi =
            $this->bersihkanPlat(
                $request->input('nomor_polisi')
                ?? $request->input('nomor_plat')
            )
            ?? $this->bersihkanPlat($tiket->nomor_polisi);

        if (!$nomorPolisi) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor plat wajib diisi.',
            ], 422);
        }

        $totalBayar = $request->input('total_bayar', 0);
        $totalBayar = is_numeric($totalBayar) ? (float) $totalBayar : 0;

        if ($totalBayar < 0) {
            $totalBayar = 0;
        }

        $tiket->update([
            'jenis' => $jenisKendaraan,
            'nomor_polisi' => $nomorPolisi,
            'total_bayar' => $totalBayar,
            'waktu_keluar' => now(),
            'status' => 'keluar',
        ]);

        $tiket->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil keluar dan pembayaran berhasil disimpan.',
            'data' => $tiket,
        ]);
    }

    /**
     * ==========================================================
     * SEMUA TIKET
     * ==========================================================
     */
    public function index()
    {
        $tiket = TiketParkir::latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => $tiket,
        ]);
    }

    /**
     * ==========================================================
     * LAPORAN / RIWAYAT TRANSAKSI GABUNGAN
     * (NON-MEMBER dari tiket_parkir + MEMBER dari member_parkir_logs)
     * ==========================================================
     |
     | Non-member : satu baris per tiket_parkir.
     | Member     : satu baris per KUNJUNGAN, dibaca dari tabel
     |              member_parkir_logs. Setiap scan masuk membuat
     |              baris baru, jadi masuk -> keluar -> masuk lagi
     |              tampil sebagai 2 baris.
     |
     */
    public function transaksi()
    {
        // ---- Non-member ----
        $tiketNonMember = TiketParkir::latest('id')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => 'tiket-' . $t->id,
                    'kode_tiket' => $t->kode_tiket,
                    'plat_nomor' => $t->nomor_polisi ?: '-',
                    'kendaraan' => $t->jenis,
                    'masuk' => $t->waktu_masuk,
                    'keluar' => $t->waktu_keluar,
                    'total' => (float) $t->total_bayar,
                    // Frontend nge-cek 'lunas' / 'belum bayar', bukan 'masuk' / 'keluar'.
                    'status' => strtolower(trim((string) $t->status)) === 'keluar'
                        ? 'lunas'
                        : 'belum bayar',
                    'tipe' => 'non_member',
                ];
            });

        // ---- Member: satu baris per kunjungan (dari member_parkir_logs) ----
        $tiketMember = MemberParkirLog::with('member')
            ->latest('waktu_masuk')
            ->get()
            ->map(function ($log) {
                $m = $log->member;
                $jenis = $log->jenis_kendaraan ?: ($m->jenis_kendaraan ?? null);

                $labelKendaraan = 'Member - ' . ($m->nama_member ?? '-');

                if ($jenis) {
                    $labelKendaraan .= ' (' . ucfirst($jenis) . ')';
                }

                return [
                    'id' => 'member-log-' . $log->id,
                    'kode_tiket' => $m->kode_member ?? '-',
                    'plat_nomor' => $log->nomor_polisi ?: '-',
                    'kendaraan' => $labelKendaraan,
                    'masuk' => $log->waktu_masuk,
                    'keluar' => $log->waktu_keluar,
                    'total' => 0, // member sudah bayar bulanan, bukan per-transaksi
                    'status' => 'lunas',
                    'tipe' => 'member',
                ];
            });

        // ---- Gabungkan & urutkan berdasarkan waktu masuk terbaru ----
        $gabungan = $tiketNonMember
            ->concat($tiketMember)
            ->sortByDesc(function ($item) {
                return strtotime((string) $item['masuk']);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $gabungan,
        ]);
    }

    /**
     * ==========================================================
     * DETAIL TIKET
     * ==========================================================
     */
    public function show($id)
    {
        $tiket = TiketParkir::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tiket,
        ]);
    }

    /**
     * ==========================================================
     * HAPUS TIKET
     * ==========================================================
     */
    public function destroy($id)
    {
        $tiket = TiketParkir::findOrFail($id);
        $tiket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dihapus.',
        ]);
    }
}