<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active'
    ];
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    public function transaksi() {
        return $this->hasMany(Transaksi::class);
    }
    public function barangMasuk() {
        return $this->hasMany(BarangMasuk::class);
    }
    public function pergerakanStok() {
        return $this->hasMany(PergerakanStok::class);
    }
    public function penyesuaianDiajukan() {
        return $this->hasMany(PenyesuaianStok::class, 'diajukan_oleh');
    }
    public function penyesuaianDisetujui() {
        return $this->hasMany(PenyesuaianStok::class, 'disetujui_oleh');
    }
    public function logAktivitas() {
        return $this->hasMany(LogAktivitas::class);
    }
    public function logBackup() {
        return $this->hasMany(LogBackup::class);
    }
}
