<?php

namespace App\Services;

use App\Exceptions\TransaksiGagal;
use App\Models\Menu;
use App\Models\PengaturanCafe;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransaksiService
{
    public function __construct(private StokService $stok)
    {
    }

    public function buat(array $data, User $kasir): Transaksi
    {
        return DB::transaction(function () use ($data, $kasir) {
            // Kunci satu baris sebagai "antrean", supaya nomor invoice
            // tidak bentrok kalau dua kasir membayar di detik yang sama.
            PengaturanCafe::lockForUpdate()->first();

            $baris = collect($data['item']);

            // Total porsi per menu, dijumlahkan dari semua varian (untuk validasi stok)
            $jumlahPerMenu = $baris->groupBy('menu_id')
                ->map(fn ($g) => (int) $g->sum('jumlah'))
                ->all();

            // Harga selalu diambil dari database, bukan dari browser
            $menu = Menu::whereIn('id', array_keys($jumlahPerMenu))->get()->keyBy('id');

            $subtotal = 0;
            $detail   = [];

            foreach ($baris as $b) {
                $m      = $menu[$b['menu_id']];
                $varian = $m->pakai_varian;
                $ukuran = $varian ? ($b['ukuran'] ?? 'small') : null;
                $harga  = $m->hargaUntuk($ukuran);
                $jumlah = (int) $b['jumlah'];

                $subtotal += $harga * $jumlah;

                $detail[] = [
                    'menu_id'    => $m->id,
                    'jumlah'     => $jumlah,
                    'harga'      => $harga,
                    'subtotal'   => $harga * $jumlah,
                    'temperatur' => $varian ? ($b['temperatur'] ?? 'ice') : null,
                    'ukuran'     => $ukuran,
                    'gula'       => $varian ? ($b['gula'] ?? 'normal') : null,
                    'catatan'    => $b['catatan'] ?? null,
                ];
            }

            $pajak = round($subtotal * config('cafe.pajak_persen') / 100);
            $total = $subtotal + $pajak;

            if ($data['metode'] === 'tunai') {
                $bayar = (float) ($data['jumlah_bayar'] ?? 0);

                if ($bayar < $total) {
                    throw new TransaksiGagal('Uang diterima kurang dari total pembayaran.');
                }
            } else {
                $bayar = $total;   // QRIS: nominal pas
            }

            // Validasi stok sebelum ada data yang ditulis (fitur 46)
            $kebutuhan = $this->stok->kebutuhan(array_map(fn ($d) => [
                'menu_id'    => $d['menu_id'],
                'jumlah'     => $d['jumlah'],
                'ukuran'     => $d['ukuran'],
                'temperatur' => $d['temperatur'],
                'gula' => $d['gula'],
            ], $detail));
            $bahan     = $this->stok->kunciDanValidasi($kebutuhan);

            $sekarang = now();
            $urut     = Transaksi::whereDate('tanggal_transaksi', $sekarang->toDateString())->count() + 1;

            $transaksi = Transaksi::create([
                'user_id'           => $kasir->id,
                'no_invoice'        => 'INV-' . $sekarang->format('Ymd') . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT),
                'tipe_pesanan'      => $data['tipe_pesanan'],
                'nama_customer'     => $data['nama_customer'] ?? null,
                'tanggal_transaksi' => $sekarang,
                'subtotal'          => $subtotal,
                'pajak'             => $pajak,
                'total'             => $total,
                'status'            => 'selesai',
            ]);

            foreach ($detail as $d) {
                $transaksi->detail()->create($d);
            }

            $transaksi->pembayaran()->create([
                'jumlah_bayar' => $bayar,
                'kembalian'    => $bayar - $total,
                'metode'       => $data['metode'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'status'       => 'berhasil',
            ]);

            // Pembayaran sudah tercatat, baru stok dikurangi
            $this->stok->kurangi($transaksi, $kebutuhan, $bahan);

            return $transaksi;
        });
    }

    public function batalkan(Transaksi $transaksi, User $user, string $alasan): void
    {
        DB::transaction(function () use ($transaksi, $alasan) {
            // Kunci baris transaksi supaya dua pembatalan bersamaan tidak lolos keduanya
            $t = Transaksi::lockForUpdate()->findOrFail($transaksi->id);

            if ($t->status === 'batal') {
                throw new TransaksiGagal('Transaksi ini sudah dibatalkan sebelumnya.');
            }

            $this->stok->kembalikan($t);

            $t->update([
                'status'       => 'batal',
                'alasan_batal' => $alasan,
            ]);

            $t->pembayaran()->update(['status' => 'dibatalkan']);
        });
    }
}