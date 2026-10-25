<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    protected $table = 'barang_masuk';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'datetime',
    ];
    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function detail() {
        return $this->hasMany(DetailBarangMasuk::class);
    }
    public function pergerakanStok() {
        return $this->hasMany(PergerakanStok::class);
    }
}
