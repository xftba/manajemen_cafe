<?php

namespace App\Http\Controllers;

use App\Models\LogBackup;
use App\Models\PengaturanCafe;
use App\Services\AktivitasService;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    public function index(Request $request, BackupService $backup)
    {
        $riwayat = LogBackup::with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate(8, ['*'], 'halaman')
            ->appends(['tab' => 'backup']);

        // Tandai riwayat yang berkasnya masih ada di server
        $riwayat->getCollection()->transform(function ($item) use ($backup) {
            $jalur = $backup->jalurLog($item);
            $item->setAttribute('file_ada', $jalur !== null);
            $item->setAttribute('ukuran', $jalur ? filesize($jalur) : null);

            return $item;
        });

        return view('pengaturan.index', [
            'pengaturan' => PengaturanCafe::ambil(),
            'riwayat'    => $riwayat,
            'terakhir'   => LogBackup::where('tipe', 'backup')->latest('created_at')->latest('id')->first(),
            'tab'        => $this->tabAwal($request),
        ]);
    }

    public function profil(Request $request)
    {
        $data = $request->validate([
            'nama_cafe' => ['required', 'string', 'max:100'],
            'alamat'    => ['nullable', 'string', 'max:255'],
            'telepon'   => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
        ], [
            'nama_cafe.required' => 'Nama café wajib diisi.',
            'telepon.regex'      => 'Telepon hanya boleh berisi angka, spasi, tanda + - dan tanda kurung.',
        ]);

        PengaturanCafe::ambil()->update([
            'nama_cafe' => trim($data['nama_cafe']),
            'alamat'    => $data['alamat'] ?? null,
            'telepon'   => $data['telepon'] ?? null,
        ]);

        AktivitasService::catat('ubah', 'pengaturan', 'Mengubah profil café');

        return redirect()->route('pengaturan.index')->with('tab', 'profil')->with('sukses', 'Profil café berhasil disimpan.');
    }

    public function logo(Request $request)
    {
        return $this->simpanGambar($request, 'logo', 'logo', 'Logo');
    }

    public function qris(Request $request)
    {
        return $this->simpanGambar($request, 'qris', 'qris_gambar', 'Gambar QRIS');
    }

    public function hapusLogo()
    {
        return $this->hapusGambar('logo', 'logo', 'Logo');
    }

    public function hapusQris()
    {
        return $this->hapusGambar('qris', 'qris_gambar', 'Gambar QRIS');
    }

    // $input = nama field dan folder, $kolom = kolom di tabel pengaturan_cafe
    private function simpanGambar(Request $request, string $input, string $kolom, string $label)
    {
        $request->validate([
            $input => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            "{$input}.required" => "Pilih file {$label} terlebih dahulu.",
            "{$input}.image"    => 'File harus berupa gambar.',
            "{$input}.mimes"    => 'Format yang diizinkan: JPG, PNG, atau WebP.',
            "{$input}.max"      => 'Ukuran file maksimal 2 MB.',
            "{$input}.uploaded" => 'File gagal diunggah. Periksa batas ukuran unggahan di server.',
        ]);

        $pengaturan = PengaturanCafe::ambil();
        $lama       = $pengaturan->{$kolom};

        // Simpan yang baru dulu, baru hapus yang lama, supaya gagal simpan tidak menghilangkan gambar lama
        $baru = $request->file($input)->store($input, 'public');
        $pengaturan->update([$kolom => $baru]);

        if ($lama) {
            Storage::disk('public')->delete($lama);
        }

        AktivitasService::catat('ubah', 'pengaturan', "Mengganti {$label} café");

        return redirect()->route('pengaturan.index')->with('tab', $input)->with('sukses', "{$label} berhasil diperbarui.");
    }

    private function hapusGambar(string $tab, string $kolom, string $label)
    {
        $pengaturan = PengaturanCafe::ambil();

        if ($pengaturan->{$kolom}) {
            Storage::disk('public')->delete($pengaturan->{$kolom});
            $pengaturan->update([$kolom => null]);

            AktivitasService::catat('hapus', 'pengaturan', "Menghapus {$label} café");
        }

        return redirect()->route('pengaturan.index')->with('tab', $tab)->with('sukses', "{$label} dihapus.");
    }

    // Tab yang terbuka saat halaman dimuat: dari flash, dari URL, atau tab yang berisi kesalahan validasi
    private function tabAwal(Request $request): string
    {
        $tab   = session('tab') ?: $request->query('tab');
        $galat = $request->session()->get('errors');

        if (! $tab && $galat) {
            foreach (['logo' => 'logo', 'qris' => 'qris', 'berkas' => 'backup', 'password' => 'backup'] as $kolom => $nama) {
                if ($galat->has($kolom)) {
                    $tab = $nama;
                    break;
                }
            }
        }

        return in_array($tab, ['profil', 'logo', 'qris', 'backup'], true) ? $tab : 'profil';
    }
}