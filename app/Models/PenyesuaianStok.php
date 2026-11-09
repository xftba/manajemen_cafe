<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyesuaianStok extends Model
{
    protected $table = 'penyesuaian_stok';
    protected $guarded = [];
    protected $casts = [
        'stok_sistem' => 'decimal:2',
        'stok_fisik'  => 'decimal:2',
    ];
    public function bahanBaku() {
        return $this->belongsTo(BahanBaku::class);
    }
    public function pengaju() {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }
    public function penyetuju() {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
    public function pergerakanStok() {
        return $this->hasMany(PergerakanStok::class);
    }
    // Selisih yang diterapkan ke stok saat pengajuan disetujui
    public function getSelisihAttribute(): float
    {
        return round((float) $this->stok_fisik - (float) $this->stok_sistem, 2);
    }
}
