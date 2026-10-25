<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PergerakanStok extends Model
{
    protected $table = 'pergerakan_stok';
    protected $guarded = [];
    const UPDATED_AT = null;
    public function bahanBaku() {
        return $this->belongsTo(BahanBaku::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function transaksi() {
        return $this->belongsTo(Transaksi::class);
    }
    public function barangMasuk() {
        return $this->belongsTo(BarangMasuk::class);
    }
    public function penyesuaianStok() {
        return $this->belongsTo(PenyesuaianStok::class);
    }
}
