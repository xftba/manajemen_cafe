<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBackup extends Model
{
    protected $table = 'log_backup';
    protected $guarded = [];
    const UPDATED_AT = 'null';
    public function user() {
        return $this->belongsTo(User::class);
    }
}
