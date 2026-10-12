<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PetugasController extends Controller
{
    /**
     * Menampilkan semua petugas
     */
    public function index()
    {
        $petugas = User::where('role', 'petugas')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $petugas
        ]);
    }

    /**
     * Menambahkan petugas baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'shift' => ['required', Rule::in(['pagi', 'siang', 'malam'])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $petugas = User::create([
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'nomor_hp' => $validated['nomor_hp'],
            'password' => Hash::make($validated['password']),
            'role' => 'petugas',
            'shift' => $validated['shift'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Petugas berhasil ditambahkan.',
            'data' => $petugas
        ], 201);
    }

    /**
     * Mengupdate petugas
     */
    public function update(Request $request, $id)
    {
        $petugas = User::where('role', 'petugas')
            ->findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($petugas->id),
            ],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
            'shift' => ['required', Rule::in(['pagi', 'siang', 'malam'])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $petugas->name = $validated['nama'];
        $petugas->email = $validated['email'];
        $petugas->nomor_hp = $validated['nomor_hp'];
        $petugas->role = 'petugas';
        $petugas->shift = $validated['shift'];
        $petugas->status = $validated['status'];

        // Password hanya diubah kalau diisi
        $passwordBerubah = false;

        if (!empty($validated['password'])) {
            $petugas->password = Hash::make($validated['password']);
            $passwordBerubah = true;
        }

        $petugas->save();

        // Paksa logout (cabut semua token Sanctum) kalau:
        // - status diubah jadi nonaktif, atau
        // - password petugas diganti
        if ($petugas->status !== 'aktif' || $passwordBerubah) {
            $petugas->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data petugas berhasil diperbarui.',
            'data' => $petugas
        ]);
    }

    /**
     * Menghapus petugas
     */
    public function destroy($id)
    {
        $petugas = User::where('role', 'petugas')
            ->findOrFail($id);

        // Backend juga jaga aturan yang sama dengan frontend:
        // petugas aktif tidak boleh dihapus
        if ($petugas->status === 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Petugas aktif tidak dapat dihapus. Ubah status menjadi Nonaktif terlebih dahulu.'
            ], 422);
        }

        // Bersihkan token sebelum akun dihapus
        $petugas->tokens()->delete();

        $petugas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Petugas berhasil dihapus.'
        ]);
    }
}