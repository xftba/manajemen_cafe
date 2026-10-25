<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function kategori() {
        return $this->belongsTo(Kategori::class);
    }
    public function resep() {
        return $this->hasMany(Resep::class);
    }
    public function detailTransaksi() {
        return $this->hasMany(DetailTransaksi::class);
    }
    public function bahanBaku() {
        return $this->belongsToMany(BahanBaku::class, 'resep')
            ->withPivot('takaran');
    }
}
