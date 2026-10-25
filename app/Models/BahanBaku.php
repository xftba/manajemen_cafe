<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBaku extends Model
{
    protected $table = 'bahan_baku';
    protected $guarded = [];
    public function resep() {
        return $this->hasMany(Resep::class);
    }
    public function detailBarangMasuk() {
        return $this->hasMany(DetailBarangMasuk::class);
    }
    public function pergerakanStok() {
        return $this->hasMany(PergerakanStok::class);
    }
    public function penyesuaianStok() {
        return $this->hasMany(PenyesuaianStok::class);
    }
    public function menu() {
        return $this->belongsToMany(Menu::class, 'resep')
            ->withPivot('takaran');
    }
}
