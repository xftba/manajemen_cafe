<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBackup extends Model
{
    const UPDATED_AT = null;

    public const TIPE = [
        'backup'      => 'Backup',
        'pra-restore' => 'Backup pengaman',
        'restore'     => 'Restore',
    ];

    protected $table = 'log_backup';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}