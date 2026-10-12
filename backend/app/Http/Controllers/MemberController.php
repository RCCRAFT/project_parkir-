<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberController extends Controller
{
    private $hargaMember = 150000;

    public function index()
    {
        $members = Member::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $members
        ]);
    }

    private function generateKodeMember()
    {
        do {
            $kode = 'MBR-' . strtoupper(Str::random(6));
        } while (
            Member::where('kode_member', $kode)->exists()
        );

        return $kode;
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_member' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'jumlah_bayar' => 'nullable|numeric|min:0',
        ]);

        $jumlahBayar = (float) ($request->jumlah_bayar ?? 0);
        $totalHarga = (float) $this->hargaMember;
        $kembalian = 0;

        if ($jumlahBayar >= $totalHarga) {
            $kembalian = $jumlahBayar - $totalHarga;
        }

        $status = $jumlahBayar >= $totalHarga ? 'lunas' : 'belum lunas';

        $today = Carbon::now();
        $bulanSekarang = $today->format('Y-m');
        $tanggalMulai = $today->copy()->startOfMonth()->format('Y-m-d');
        $tanggalExpired = $today->copy()->endOfMonth()->format('Y-m-d');
        $tanggalBayar = ($status === 'lunas') ? Carbon::now() : null;

        $member = Member::create([
            'kode_member' => $this->generateKodeMember(),
            'token' => Str::random(32),
            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,
            'total_harga' => $totalHarga,
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,
            'status' => $status,
            'status_parkir' => 'di_luar',
            'waktu_masuk_terakhir' => null,
            'waktu_keluar_terakhir' => null,
            'bulan_tagihan' => $bulanSekarang,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_expired' => $tanggalExpired,
            'tanggal_bayar' => $tanggalBayar,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Member berhasil ditambahkan',
            'data' => $member
        ], 201);
    }

    public function show($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $isiQr = $member->kode_member;

        $qr = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->generate($isiQr);

        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qr);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $member->id,
                'kode_member' => $member->kode_member,
                'token' => $member->token,
                'nama_member' => $member->nama_member,
                'nama_perusahaan' => $member->nama_perusahaan,
                'total_harga' => (float) $member->total_harga,
                'jumlah_bayar' => (float) ($member->jumlah_bayar ?? 0),
                'kembalian' => (float) ($member->kembalian ?? 0),
                'status' => $member->status,
                'bulan_tagihan' => $member->bulan_tagihan,
                'tanggal_bayar' => $member->tanggal_bayar,
                'tanggal_mulai' => $member->tanggal_mulai,
                'tanggal_expired' => $member->tanggal_expired,
                'qr' => $qrBase64,
            ]
        ]);
    }

    public function pembayaran(Request $request, $id)
    {
        $request->validate([
            'jumlah_bayar' => 'required|numeric|min:0'
        ]);

        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $this->buatTagihanMemberJikaPerlu($member);
        $member->refresh();

        $hargaMember = (float) ($member->total_harga ?: $this->hargaMember);
        $jumlahBayar = (float) $request->jumlah_bayar;

        if ($jumlahBayar < $hargaMember) {
            return response()->json([
                'status' => false,
                'message' => 'Pembayaran kurang',
                'data' => [
                    'id' => $member->id,
                    'tagihan' => $hargaMember,
                    'jumlah_bayar' => $jumlahBayar,
                    'kekurangan' => $hargaMember - $jumlahBayar,
                    'status' => 'belum lunas',
                ]
            ], 422);
        }

        $kembalian = $jumlahBayar - $hargaMember;
        $tanggalBayar = Carbon::now();

        $member->update([
            'status' => 'lunas',
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,
            'tanggal_bayar' => $tanggalBayar,
        ]);

        $member->refresh();

        return response()->json([
            'status' => true,
            'message' => 'Pembayaran berhasil',
            'data' => [
                'id' => $member->id,
                'kode_member' => $member->kode_member,
                'token' => $member->token,
                'nama_member' => $member->nama_member,
                'nama_perusahaan' => $member->nama_perusahaan,
                'total_harga' => (float) $member->total_harga,
                'jumlah_bayar' => (float) $member->jumlah_bayar,
                'kembalian' => (float) $member->kembalian,
                'status' => $member->status,
                'bulan_tagihan' => $member->bulan_tagihan,
                'tanggal_bayar' => $member->tanggal_bayar,
                'tanggal_expired' => $member->tanggal_expired,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'nama_member' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
        ]);

        $member->update([
            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data member berhasil diperbarui',
            'data' => $member
        ]);
    }

    public function destroy($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $member->delete();

        return response()->json([
            'status' => true,
            'message' => 'Member berhasil dihapus'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK QR / MEMBER MASUK
    |--------------------------------------------------------------------------
    */

    public function check(Request $request)
    {
        $request->validate([
            'kode_member' => 'nullable|string',
            'token' => 'nullable|string',
            'nomor_polisi' => 'nullable|string|max:20',
        ]);

        $kodeMember = trim((string) $request->kode_member);
        $token = trim((string) $request->token);

        if ($kodeMember === '' && $token === '') {
            return response()->json([
                'status' => false,
                'message' => 'Kode member wajib diisi'
            ], 422);
        }

        return DB::transaction(function () use ($kodeMember, $token, $request) {

            $member = $kodeMember !== ''
                ? Member::where('kode_member', $kodeMember)->lockForUpdate()->first()
                : Member::where('token', $token)->lockForUpdate()->first();

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            $this->buatTagihanMemberJikaPerlu($member);
            $member->refresh();

            $bulanSekarang = Carbon::now()->format('Y-m');

            if ($member->bulan_tagihan !== $bulanSekarang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tagihan bulan ini belum dibuat',
                    'data' => $member
                ], 400);
            }

            if (strtolower(trim($member->status)) !== 'lunas') {
                return response()->json([
                    'status' => false,
                    'message' => 'Tagihan bulan ini belum dibayar',
                    'data' => $member
                ], 400);
            }

            if (
                !$member->tanggal_expired ||
                Carbon::now()->greaterThan(
                    Carbon::parse($member->tanggal_expired)->endOfDay()
                )
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Masa berlaku member sudah berakhir',
                    'data' => $member
                ], 400);
            }

            $statusParkir = strtolower(trim((string) ($member->status_parkir ?? 'di_luar')));

            if ($statusParkir === 'sedang_masuk') {
                return response()->json([
                    'status' => false,
                    'already_in' => true,
                    'message' => "Member {$member->kode_member} sudah berada di area parkir.",
                    'data' => $member
                ], 409);
            }

            $plat = $request->nomor_polisi
                ? strtoupper(trim($request->nomor_polisi))
                : null;

            $member->update([
                'status_parkir' => 'sedang_masuk',
                'waktu_masuk_terakhir' => now(),
                'nomor_polisi' => $plat ?? $member->nomor_polisi,
            ]);

            // Catat log masuk (hanya kalau benar-benar berhasil masuk)
            MemberLog::create([
                'member_id' => $member->id,
                'nomor_polisi' => $plat ?? $member->nomor_polisi,
                'masuk' => now(),
            ]);

            $member->refresh();

            return response()->json([
                'status' => true,
                'already_in' => false,
                'message' => "Member {$member->nama_member} berhasil masuk parkir.",
                'data' => $member
            ], 200);
        });
    }

    public function cariByKode($kode)
    {
        $kode = trim((string) $kode);

        $member = Member::where('kode_member', $kode)
            ->orWhere('token', $kode)
            ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $member
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MEMBER KELUAR
    |--------------------------------------------------------------------------
    */

    public function keluar(Request $request)
    {
        $request->validate([
            'kode_member' => 'nullable|string',
            'token' => 'nullable|string',
            'jenis_kendaraan' => 'nullable|string|in:motor,mobil',
            'nomor_polisi' => 'nullable|string|max:20',
        ]);

        $kodeMember = trim((string) $request->kode_member);
        $token = trim((string) $request->token);

        if ($kodeMember === '' && $token === '') {
            return response()->json([
                'status' => false,
                'message' => 'Kode member wajib diisi'
            ], 422);
        }

        return DB::transaction(function () use ($kodeMember, $token, $request) {

            $member = $kodeMember !== ''
                ? Member::where('kode_member', $kodeMember)->lockForUpdate()->first()
                : Member::where('token', $token)->lockForUpdate()->first();

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            $statusParkir = strtolower(trim((string) ($member->status_parkir ?? 'di_luar')));

            if ($statusParkir !== 'sedang_masuk') {
                return response()->json([
                    'status' => false,
                    'message' => "Member {$member->kode_member} belum tercatat masuk parkir.",
                    'data' => $member
                ], 409);
            }

            $plat = $request->nomor_polisi
                ? strtoupper(trim($request->nomor_polisi))
                : null;

            $member->update([
                'status_parkir' => 'di_luar',
                'waktu_keluar_terakhir' => now(),
                'jenis_kendaraan' => $request->jenis_kendaraan ?? $member->jenis_kendaraan,
                'nomor_polisi' => $plat ?? $member->nomor_polisi,
            ]);

            // Tutup log masuk yang masih terbuka
            $log = MemberLog::where('member_id', $member->id)
                ->whereNull('keluar')
                ->latest('masuk')
                ->first();

            if ($log) {
                $log->update([
                    'keluar' => now(),
                    'nomor_polisi' => $plat ?? $log->nomor_polisi,
                ]);
            }

            $member->refresh();

            return response()->json([
                'status' => true,
                'message' => "Member {$member->nama_member} berhasil keluar parkir.",
                'data' => $member
            ], 200);
        });
    }

    public function resetBulanan()
    {
        $jumlahTagihan = $this->buatTagihanBulanIni();
        $bulanSekarang = Carbon::now()->format('Y-m');

        return response()->json([
            'status' => true,
            'message' => "Tagihan bulan $bulanSekarang berhasil dibuat",
            'jumlah_tagihan' => $jumlahTagihan
        ]);
    }

    private function buatTagihanBulanIni()
    {
        $today = Carbon::now();
        $bulanSekarang = $today->format('Y-m');
        $tanggalMulai = $today->copy()->startOfMonth()->format('Y-m-d');
        $tanggalExpired = $today->copy()->endOfMonth()->format('Y-m-d');

        $members = Member::all();
        $jumlahTagihan = 0;

        foreach ($members as $member) {
            if ($member->bulan_tagihan === $bulanSekarang) {
                continue;
            }

            $member->update([
                'total_harga' => $this->hargaMember,
                'jumlah_bayar' => 0,
                'kembalian' => 0,
                'status' => 'belum lunas',
                'tanggal_bayar' => null,
                'bulan_tagihan' => $bulanSekarang,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_expired' => $tanggalExpired,
            ]);

            $jumlahTagihan++;
        }

        return $jumlahTagihan;
    }

    private function buatTagihanMemberJikaPerlu(Member $member)
    {
        $bulanSekarang = Carbon::now()->format('Y-m');

        if ($member->bulan_tagihan === $bulanSekarang) {
            return;
        }

        $today = Carbon::now();
        $tanggalMulai = $today->copy()->startOfMonth()->format('Y-m-d');
        $tanggalExpired = $today->copy()->endOfMonth()->format('Y-m-d');

        $member->update([
            'total_harga' => $this->hargaMember,
            'jumlah_bayar' => 0,
            'kembalian' => 0,
            'status' => 'belum lunas',
            'tanggal_bayar' => null,
            'bulan_tagihan' => $bulanSekarang,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_expired' => $tanggalExpired,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LAPORAN LOG MEMBER
    |--------------------------------------------------------------------------
    */

    public function logMember()
    {
        $data = MemberLog::with('member')
            ->orderByDesc('masuk')
            ->limit(500)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'kode_member' => $l->member->kode_member ?? null,
                'plat_nomor' => $l->nomor_polisi ?? $l->member->nomor_polisi ?? '-',
                'masuk' => $l->masuk,
                'keluar' => $l->keluar,
                'total' => 0,
                'status' => 'lunas',
            ]);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}