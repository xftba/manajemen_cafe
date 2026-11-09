<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    const UPDATED_AT = null;

    protected $table = 'log_aktivitas';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}