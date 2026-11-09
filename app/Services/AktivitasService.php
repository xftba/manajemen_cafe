<?php

namespace App\Services;

use App\Models\LogAktivitas;

class AktivitasService
{
    public static function catat(string $aksi, string $modul, ?string $deskripsi = null): void
    {
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aksi'      => $aksi,
            'modul'     => $modul,
            'deskripsi' => $deskripsi,
        ]);
    }
}