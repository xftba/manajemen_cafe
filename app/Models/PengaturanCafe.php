<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanCafe extends Model
{
    protected $table = 'pengaturan_cafe';
    protected $guarded = [];

    // Selalu mengembalikan satu baris pengaturan. Dibuat otomatis kalau belum ada.
    public static function ambil(): self
    {
        return static::first() ?? static::create(['nama_cafe' => config('app.name')]);
    }
}