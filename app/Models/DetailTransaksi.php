<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksi';
    protected $guarded = [];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    // Contoh hasil: "Ice • Small • Normal Sugar" (kosong untuk menu tanpa varian)
    public function getVarianAttribute(): string
    {
        return collect([
            $this->temperatur ? config('cafe.temperatur.' . $this->temperatur) : null,
            $this->ukuran ? config('cafe.ukuran.' . $this->ukuran) : null,
            $this->gula ? config('cafe.gula.' . $this->gula) : null,
        ])->filter()->implode(' • ');
    }
}