<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';
    protected $guarded = [];

    protected $casts = [
        'harga'           => 'decimal:2',
        'tambahan_medium' => 'decimal:2',
        'tambahan_large'  => 'decimal:2',
        'pakai_varian'    => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function resep()
    {
        return $this->hasMany(\App\Models\Resep::class);
    }

    // Jumlah menu yang habis (porsi 0 atau belum ada resep).
    public static function jumlahHabis(): int
    {
        return static::with(['kategori', 'resep.bahanBaku'])
            ->get()
            ->reject(fn ($m) => $m->ringkasan()['tersedia'])
            ->count();
    }

    // Harga satuan menurut ukuran. Small = harga dasar.
    public function hargaUntuk(?string $ukuran): float
    {
        $dasar = (float) $this->harga;

        if (! $this->pakai_varian) {
            return $dasar;
        }

        return match ($ukuran) {
            'medium' => $dasar + (float) $this->tambahan_medium,
            'large'  => $dasar + (float) $this->tambahan_large,
            default  => $dasar,
        };
    }

    // Porsi yang bisa dibuat dari stok saat ini untuk satu ukuran.
    // null = resep ukuran itu belum dibuat. Relasi resep.bahanBaku harus sudah dimuat.
    public function porsiUkuran(string $ukuran): ?int
    {
        $baris = $this->resep->where('ukuran', $ukuran);

        if ($baris->isEmpty()) {
            return null;
        }

        return max(0, (int) $baris
            ->map(fn ($r) => (float) $r->takaran > 0
                ? floor((float) $r->bahanBaku->stok / (float) $r->takaran)
                : 0)
            ->min());
    }

    // Porsi terbanyak yang bisa dibuat dari semua ukuran yang dijual
    public function porsiTersedia(): int
    {
        $ukuran = $this->pakai_varian ? array_keys(config('cafe.ukuran')) : ['small'];

        return (int) collect($ukuran)->map(fn ($u) => $this->porsiUkuran($u) ?? 0)->max();
    }

    public const BATAS_MENIPIS = 5;

    // Relasi kategori dan resep.bahanBaku harus sudah dimuat
    public function ringkasan(): array
    {
        $ukuran  = $this->pakai_varian ? array_keys(config('cafe.ukuran')) : ['small'];
        $terbaik = null;

        $hitung = fn ($r) => (float) $r->takaran > 0
            ? (int) floor((float) $r->bahanBaku->stok / (float) $r->takaran)
            : 0;

        foreach ($ukuran as $u) {
            $semua    = $this->resep->where('ukuran', $u);
            $kandidat = [$semua];

            // Menu varian bisa dibuat Hot tanpa bahan "hanya ice" (mis. es batu)
            if ($this->pakai_varian) {
                $kandidat[] = $semua->filter(fn ($r) => ! $r->hanya_ice);
            }

            foreach ($kandidat as $baris) {
                if ($baris->isEmpty()) {
                    continue;
                }

                $penentu = $baris->sortBy($hitung)->first();
                $porsi   = $hitung($penentu);

                if ($terbaik === null || $porsi > $terbaik['porsi']) {
                    $terbaik = ['porsi' => $porsi, 'bahan' => $penentu->bahanBaku];
                }
            }
        }

        $porsi = $terbaik['porsi'] ?? 0;
        $bahan = $terbaik['bahan'] ?? null;
        $sisa  = $bahan ? BahanBaku::angka($bahan->stok) . ' ' . $bahan->satuan : '';

        if (! $bahan) {
            [$status, $catatan, $tipe] = ['habis', 'Belum ada resep', 'peringatan'];
        } elseif ($porsi === 0) {
            $catatan = (float) $bahan->stok <= 0
                ? "{$bahan->nama} kosong (0 {$bahan->satuan})"
                : "{$bahan->nama} tidak cukup (sisa {$sisa})";
            [$status, $tipe] = ['habis', 'bahaya'];
        } elseif ($porsi <= self::BATAS_MENIPIS || $bahan->menipis()) {
            [$status, $catatan, $tipe] = ['menipis', "{$bahan->nama} sisa {$sisa}", 'info'];
        } else {
            $status  = 'tersedia';
            $tipe    = 'netral';
            $catatan = $this->resep->map(fn ($r) => $r->bahanBaku->nama)->unique()->values()->implode(', ');
        }

        $harga = [(float) $this->harga];
        if ($this->pakai_varian) {
            $harga[] = $this->hargaUntuk('medium');
            $harga[] = $this->hargaUntuk('large');
        }

        return [
            'id'           => $this->id,
            'nama'         => $this->nama,
            'kategori_id'  => $this->kategori_id,
            'kategori'     => $this->kategori->nama,
            'gambar_url'   => $this->gambar ? asset('storage/' . $this->gambar) : null,
            'tersedia'     => $porsi > 0,
            'harga_min'    => min($harga),
            'harga_maks'   => max($harga),
            'porsi'        => $porsi,
            'status'       => $status,
            'catatan'      => $catatan,
            'tipe_catatan' => $tipe,
            'proses'       => false,
        ];
    }
}