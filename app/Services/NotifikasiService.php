<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiService
{
    // Kirim ke semua user dengan role tertentu (satu baris per penerima)
    public static function kirimKeRole(string $role, string $jenis, string $judul, ?string $isi = null, ?string $url = null): void
    {
        try {
            $ids = User::where('role', $role)->pluck('id');   // tambahkan filter akun aktif kalau ada kolomnya
            self::simpan($ids->all(), $jenis, $judul, $isi, $url);
        } catch (\Throwable $e) {
            report($e);   // notifikasi gagal tidak boleh menggagalkan proses utama
        }
    }

    public static function kirimKeUser(int $userId, string $jenis, string $judul, ?string $isi = null, ?string $url = null): void
    {
        try {
            self::simpan([$userId], $jenis, $judul, $isi, $url);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function simpan(array $userIds, string $jenis, string $judul, ?string $isi, ?string $url): void
    {
        $now  = now();
        $rows = array_map(fn ($id) => [
            'user_id'    => $id,
            'jenis'      => $jenis,
            'judul'      => $judul,
            'isi'        => $isi,
            'url'        => $url,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        if ($rows) {
            Notifikasi::insert($rows);
        }
    }
}