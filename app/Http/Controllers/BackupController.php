<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\LogBackup;
use App\Models\User;
use App\Services\AktivitasService;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class BackupController extends Controller
{
    public function buat(Request $request, BackupService $service)
    {
        try {
            $log = $service->buat('backup', $request->user());
        } catch (RuntimeException $e) {
            return $this->kembali()->with('error', 'Backup gagal dibuat: ' . $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->kembali()->with('error', 'Backup gagal dibuat. Periksa log aplikasi di storage/logs.');
        }

        AktivitasService::catat('backup', 'pengaturan', "Membuat backup database {$log->nama_file}");

        return $this->kembali()->with('sukses', "Backup berhasil dibuat: {$log->nama_file}");
    }

    public function unduh(LogBackup $logBackup, BackupService $service)
    {
        $jalur = $service->jalurLog($logBackup);
        abort_unless($jalur, 404, 'Berkas backup tidak ditemukan di server.');

        AktivitasService::catat('unduh', 'pengaturan', "Mengunduh backup {$logBackup->nama_file}");

        return response()->download($jalur, $logBackup->nama_file, ['Content-Type' => 'application/sql']);
    }

    public function hapus(LogBackup $logBackup, BackupService $service)
    {
        $jalur = $service->jalurLog($logBackup);
        abort_unless($jalur, 404, 'Berkas backup tidak ditemukan di server.');

        unlink($jalur);
        AktivitasService::catat('hapus', 'pengaturan', "Menghapus berkas backup {$logBackup->nama_file}");

        return $this->kembali()->with('sukses', 'Berkas backup dihapus. Catatan riwayatnya tetap tersimpan.');
    }

    // Restore dari backup yang tersimpan di server
    public function pulihkan(Request $request, LogBackup $logBackup, BackupService $service)
    {
        $request->validate(['password' => ['required', 'string']], [
            'password.required' => 'Masukkan password Anda untuk melanjutkan restore.',
        ]);

        $jalur = $service->jalurLog($logBackup);
        abort_unless($jalur, 404, 'Berkas backup tidak ditemukan di server.');

        return $this->jalankan($request, $service, $jalur, $logBackup->nama_file);
    }

    // Restore dari berkas yang diunggah
    public function pulihkanUnggahan(Request $request, BackupService $service)
    {
        $request->validate([
            'berkas'   => ['required', 'file', 'extensions:sql', 'max:51200'],
            'password' => ['required', 'string'],
        ], [
            'berkas.required'    => 'Pilih berkas backup (.sql) terlebih dahulu.',
            'berkas.extensions'  => 'Berkas harus berekstensi .sql.',
            'berkas.max'         => 'Ukuran berkas maksimal 50 MB.',
            'berkas.uploaded'    => 'Berkas gagal diunggah. Periksa batas ukuran unggahan di server.',
            'password.required'  => 'Masukkan password Anda untuk melanjutkan restore.',
        ]);

        $file = $request->file('berkas');

        return $this->jalankan($request, $service, $file->getRealPath(), $file->getClientOriginalName());
    }

    private function jalankan(Request $request, BackupService $service, string $jalur, string $sumber)
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            return $this->kembali()->with('error', 'Password yang Anda masukkan salah. Restore tidak dijalankan.');
        }

        // 1. Pastikan berkas asli dan utuh sebelum menyentuh database
        try {
            $service->periksa($jalur);
        } catch (RuntimeException $e) {
            return $this->kembali()->with('error', $e->getMessage());
        }

        // 2. Backup pengaman: jalan keluar kalau restore gagal di tengah
        try {
            $pengaman = $service->buat('pra-restore', $user);
        } catch (\Throwable $e) {
            report($e);

            return $this->kembali()->with('error', 'Restore dibatalkan karena backup pengaman gagal dibuat. Database tidak diubah.');
        }

        // 3. Pulihkan
        try {
            $jumlah = $service->pulihkan($jalur);
        } catch (\Throwable $e) {
            report($e);

            return $this->kembali()->with(
                'error',
                "Restore gagal di tengah proses sehingga database mungkin belum utuh. Pulihkan dari backup pengaman {$pengaman->nama_file} lewat tabel riwayat."
            );
        }

        // 4. Setelah restore, daftar akun bisa berbeda dari sebelumnya
        $akunMasihAda = User::where('id', $user->id)->where('is_active', true)->exists();
        $sumber       = Str::limit(basename($sumber), 200, '');

        LogAktivitas::create([
            'user_id'   => $akunMasihAda ? $user->id : null,
            'aksi'      => 'restore',
            'modul'     => 'pengaturan',
            'deskripsi' => "Memulihkan database dari {$sumber} oleh {$user->nama} ({$jumlah} perintah). Backup pengaman: {$pengaman->nama_file}",
        ]);

        if ($akunMasihAda) {
            LogBackup::create(['user_id' => $user->id, 'nama_file' => $sumber, 'tipe' => 'restore']);

            return $this->kembali()->with('sukses', 'Restore berhasil. Database dikembalikan ke kondisi pada berkas backup.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('sukses', 'Restore berhasil. Akun Anda tidak ada atau tidak aktif pada data yang dipulihkan, silakan login dengan akun lain.');
    }

    private function kembali()
    {
        return redirect()->route('pengaturan.index')->with('tab', 'backup');
    }
}