<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PergerakanStok extends Model
{
    const UPDATED_AT = null;

    public const LABEL = [
        'stok_awal'   => 'Stok awal',
        'masuk'       => 'Barang masuk',
        'keluar'      => 'Penjualan',
        'pembatalan'  => 'Pembatalan transaksi',
        'rusak'       => 'Rusak',
        'kadaluarsa'  => 'Kadaluarsa',
        'penyesuaian' => 'Penyesuaian stok',
    ];

    protected $table = 'pergerakan_stok';
    protected $guarded = [];

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasuk::class);
    }

    public function penyesuaianStok()
    {
        return $this->belongsTo(PenyesuaianStok::class);
    }

    // Positif = stok bertambah, negatif = stok berkurang
    public function getSelisihAttribute(): float
    {
        return round((float) $this->stok_sesudah - (float) $this->stok_sebelum, 2);
    }

    public function getLabelAttribute(): string
    {
        return self::LABEL[$this->tipe_pergerakan] ?? $this->tipe_pergerakan;
    }
}