<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailBarangMasuk extends Model
{
    protected $table = 'detail_barang_masuk';
    protected $guarded = [];

    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasuk::class);
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->jumlah * (float) $this->harga_beli;
    }
}