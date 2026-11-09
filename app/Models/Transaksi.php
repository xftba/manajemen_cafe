<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $guarded = [];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detail()
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function pergerakanStok()
    {
        return $this->hasMany(PergerakanStok::class);
    }

    // Aturan izin pembatalan
    public function bisaDibatalkanOleh(User $user): bool
    {
        if ($this->status !== 'selesai') {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        // Kasir: hanya transaksi miliknya sendiri pada hari yang sama
        return $user->role === 'kasir'
            && $this->user_id === $user->id
            && $this->tanggal_transaksi->isToday();
    }
    // Nomor urut pesanan pada hari itu, diambil dari ujung nomor invoice (INV-20261101-0002 menjadi 2)
public function getNomorOrderAttribute(): int
{
    return (int) Str::afterLast($this->no_invoice, '-');
}
}