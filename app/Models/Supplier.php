<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'supplier';
    protected $guarded = [];

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class);
    }
}