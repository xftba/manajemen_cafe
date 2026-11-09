<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBaku extends Model
{
    protected $table = 'bahan_baku';
    protected $guarded = [];

    protected $casts = [
        'stok'         => 'decimal:2',
        'stok_minimum' => 'decimal:2',
    ];

    public function resep()
    {
        return $this->hasMany(Resep::class);
    }

    public function detailBarangMasuk()
    {
        return $this->hasMany(DetailBarangMasuk::class);
    }

    public function pergerakanStok()
    {
        return $this->hasMany(PergerakanStok::class);
    }

    public function penyesuaianStok()
    {
        return $this->hasMany(PenyesuaianStok::class);
    }

    public function menu()
    {
        return $this->belongsToMany(Menu::class, 'resep')
            ->withPivot('takaran');
    }

    // Stok sama dengan atau di bawah batas minimum (fitur 11)
    public function menipis(): bool
    {
        return (float) $this->stok <= (float) $this->stok_minimum;
    }

    // Tampilkan angka tanpa nol di belakang koma: 100,00 menjadi 100 dan 2,50 menjadi 2,5
    public static function angka($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    }
}