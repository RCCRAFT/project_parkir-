<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Tagihan;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaksi::with(['member', 'tagihan']);

        if ($request->member_id) {
            $query->where('member_id', $request->member_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show($id)
    {
        $transaksi = Transaksi::with(['member', 'tagihan'])->findOrFail($id);
        return response()->json($transaksi);
    }
}