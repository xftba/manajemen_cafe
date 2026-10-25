<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $guarded = [];
    public function transaksi() {
        return $this->belongsTo(Transaksi::class);
    }
}
