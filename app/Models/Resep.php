<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resep extends Model
{
    protected $table = 'transaksi';
    protected $guarded = [];
    protected $casts = [
        'tanggal_transaksi' => 'datetime'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function detail() {
        return $this->hasMany(DetailTransaksi::class);
    }
    public function pembayaran() {
        return $this->hasOne(Pembayaran::class);
    }
    public function pergerakanStok() {
        return $this->hasMany(PergerakanStok::class);
    }
}