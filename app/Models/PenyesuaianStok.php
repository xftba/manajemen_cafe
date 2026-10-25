<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyesuaianStok extends Model
{
    protected $table = 'penyesuaian_stok';
    protected $guarded = [];
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
}
