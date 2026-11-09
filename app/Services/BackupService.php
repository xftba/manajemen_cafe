<?php

namespace App\Services;

use App\Models\LogBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;

class BackupService
{
    private const PENANDA   = '-- MANAJEMEN-CAFE-BACKUP v1';
    private const TANDA     = '-- SIGNATURE: ';
    private const POLA_NAMA = '/^cafe-\d{8}-\d{6}-(manual|pra-restore)-[a-f0-9]{4}\.sql$/';

    // Tidak ikut dicadangkan: data sementara dan riwayat backup itu sendiri
    private const KECUALI = [
        'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'log_backup',
    ];

    public function folder(): string
    {
        Storage::disk('local')->makeDirectory('backup');

        return Storage::disk('local')->path('backup');
    }

    // Daftar tabel yang dicadangkan
    public function daftarTabel(): array
    {
        $pdo = DB::connection()->getPdo();

        return collect($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM))
            ->map(fn ($baris) => $baris[0])
            ->reject(fn ($tabel) => in_array($tabel, self::KECUALI, true))
            ->values()
            ->all();
    }

    /**
     * Buat berkas backup. Setiap perintah SQL ditulis dalam satu baris,
     * dan baris terakhir berisi tanda tangan HMAC atas seluruh isi berkas.
     *
     * @param  string  $tipe  'backup' (manual) atau 'pra-restore' (pengaman sebelum restore)
     */
    public function buat(string $tipe, User $user): LogBackup
    {
        set_time_limit(0);

        $nama  = 'cafe-' . now()->format('Ymd-His') . '-' . ($tipe === 'pra-restore' ? 'pra-restore' : 'manual')
               . '-' . bin2hex(random_bytes(2)) . '.sql';
        $jalur = $this->folder() . DIRECTORY_SEPARATOR . $nama;

        $berkas = fopen($jalur, 'wb');
        if (! $berkas) {
            throw new RuntimeException('Folder backup tidak dapat ditulis. Periksa izin folder storage.');
        }

        $hash  = hash_init('sha256', HASH_HMAC, (string) config('app.key'));
        $tulis = function (string $baris) use ($berkas, $hash) {
            $baris .= "\n";
            hash_update($hash, $baris);
            fwrite($berkas, $baris);
        };

        $pdo = DB::connection()->getPdo();

        try {
            $tulis(self::PENANDA);
            $tulis('-- Dibuat: ' . now()->toDateTimeString());
            $tulis('-- Database: ' . DB::connection()->getDatabaseName());
            $tulis('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($this->daftarTabel() as $tabel) {
                $tulis("DROP TABLE IF EXISTS `{$tabel}`;");

                $struktur = $pdo->query("SHOW CREATE TABLE `{$tabel}`")->fetch(PDO::FETCH_NUM)[1];
                $tulis(preg_replace('/\s*\R\s*/', ' ', $struktur) . ';');   // jadikan satu baris

                $kolom  = null;
                $kumpul = [];

                foreach (DB::table($tabel)->cursor() as $baris) {
                    $baris   = (array) $baris;
                    $kolom ??= '`' . implode('`,`', array_keys($baris)) . '`';

                    $kumpul[] = '(' . implode(',', array_map(
                        fn ($nilai) => $nilai === null ? 'NULL' : $pdo->quote((string) $nilai),
                        $baris
                    )) . ')';

                    if (count($kumpul) >= 100) {
                        $tulis("INSERT INTO `{$tabel}` ({$kolom}) VALUES " . implode(',', $kumpul) . ';');
                        $kumpul = [];
                    }
                }

                if ($kumpul) {
                    $tulis("INSERT INTO `{$tabel}` ({$kolom}) VALUES " . implode(',', $kumpul) . ';');
                }
            }

            $tulis('SET FOREIGN_KEY_CHECKS=1;');
            fwrite($berkas, self::TANDA . hash_final($hash) . "\n");
        } catch (\Throwable $e) {
            fclose($berkas);
            @unlink($jalur);

            throw $e;
        }

        fclose($berkas);

        return LogBackup::create([
            'user_id'   => $user->id,
            'nama_file' => $nama,
            'tipe'      => $tipe === 'pra-restore' ? 'pra-restore' : 'backup',
        ]);
    }

    /**
     * Pastikan berkas dibuat oleh aplikasi ini dan isinya belum diubah.
     * Melempar RuntimeException kalau tidak valid.
     */
    public function periksa(string $jalur): void
    {
        $berkas = @fopen($jalur, 'rb');
        if (! $berkas) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        $hash    = hash_init('sha256', HASH_HMAC, (string) config('app.key'));
        $pertama = true;
        $tanda   = null;

        while (($baris = fgets($berkas)) !== false) {
            if ($pertama) {
                $pertama = false;

                if (rtrim($baris) !== self::PENANDA) {
                    fclose($berkas);
                    throw new RuntimeException('Berkas ini bukan backup dari aplikasi Manajemen Cafe.');
                }
            }

            if (str_starts_with($baris, self::TANDA)) {
                $tanda = trim(substr($baris, strlen(self::TANDA)));
                continue;
            }

            if ($tanda !== null) {
                fclose($berkas);
                throw new RuntimeException('Berkas backup rusak: ada isi setelah tanda tangan.');
            }

            hash_update($hash, $baris);
        }

        fclose($berkas);

        if ($tanda === null || ! hash_equals(hash_final($hash), $tanda)) {
            throw new RuntimeException('Berkas backup tidak valid, sudah diubah, atau dibuat oleh instalasi lain.');
        }
    }

    /**
     * Pulihkan database dari berkas backup. Mengembalikan jumlah perintah yang dijalankan.
     * Perintah DDL di MySQL tidak bisa dibatalkan, jadi proses ini tidak atomik.
     * Karena itu pemanggil wajib membuat backup pengaman terlebih dulu.
     */
    public function pulihkan(string $jalur): int
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $this->periksa($jalur);

        $berkas = fopen($jalur, 'rb');
        $jumlah = 0;

        try {
            while (($baris = fgets($berkas)) !== false) {
                $baris = trim($baris);

                if ($baris === '' || str_starts_with($baris, '--')) {
                    continue;
                }

                DB::unprepared($baris);
                $jumlah++;
            }
        } finally {
            fclose($berkas);
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }

        return $jumlah;
    }

    // Lokasi berkas milik satu baris riwayat. null kalau bukan backup atau berkasnya sudah tidak ada.
    public function jalurLog(LogBackup $log): ?string
    {
        if (! in_array($log->tipe, ['backup', 'pra-restore'], true)) {
            return null;
        }

        // Nama berkas selalu dicocokkan dengan pola, mencegah akses ke berkas lain
        if (! preg_match(self::POLA_NAMA, $log->nama_file)) {
            return null;
        }

        $jalur = $this->folder() . DIRECTORY_SEPARATOR . $log->nama_file;

        return is_file($jalur) ? $jalur : null;
    }
}