<?php

use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\BahanRusakController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KetersediaanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\PergerakanStokController;
use App\Http\Controllers\ResepController;
use App\Http\Controllers\StokMenipisController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\PengaturanController;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth', 'aktif'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== ADMIN =====
    Route::middleware('role:admin')->group(function () {
        Route::resource('kategori', KategoriController::class)->except(['create', 'show']);
        Route::resource('menu', MenuController::class)->except(['show']);
        Route::patch('menu/{menu}/toggle', [MenuController::class, 'toggle'])->name('menu.toggle');
        Route::get('menu-status', [MenuController::class, 'status'])->name('menu.status');

        Route::resource('bahan-baku', BahanBakuController::class)
            ->except(['index', 'show'])
            ->parameters(['bahan-baku' => 'bahanBaku']);
        Route::resource('supplier', SupplierController::class)->except(['index', 'show']);

        Route::get('resep', [ResepController::class, 'index'])->name('resep.index');
        Route::get('resep/{menu}', [ResepController::class, 'edit'])->name('resep.edit');
        Route::put('resep/{menu}', [ResepController::class, 'update'])->name('resep.update');
        Route::delete('resep/{menu}', [ResepController::class, 'destroy'])->name('resep.destroy');

        Route::get('transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
        Route::post('transaksi/{transaksi}/batal', [TransaksiController::class, 'batal'])->name('transaksi.batal');
        Route::get('transaksi/{transaksi}/struk', [TransaksiController::class, 'struk'])->name('transaksi.struk');

        // Admin memutuskan pengajuan penyesuaian stok
        Route::post('penyesuaian-stok/{penyesuaianStok}/setujui', [PenyesuaianStokController::class, 'setujui'])->name('penyesuaian-stok.setujui');
        Route::post('penyesuaian-stok/{penyesuaianStok}/tolak', [PenyesuaianStokController::class, 'tolak'])->name('penyesuaian-stok.tolak');

        // Laporan (admin melihat kelima laporan)
        Route::redirect('/laporan', '/laporan/penjualan-harian')->name('laporan.index');
        Route::prefix('laporan')->name('laporan.')->controller(LaporanController::class)->group(function () {
            Route::get('penjualan-harian', 'penjualanHarian')->name('penjualan-harian');
            Route::get('penjualan-bulanan', 'penjualanBulanan')->name('penjualan-bulanan');
            Route::get('transaksi', 'transaksi')->name('transaksi');
            Route::get('penggunaan-bahan', 'penggunaanBahan')->name('penggunaan-bahan');
            Route::get('persediaan', 'persediaan')->name('persediaan');
        });

        Route::resource('pengguna', UserController::class)
            ->parameters(['pengguna' => 'user'])
            ->names('users')
            ->except(['show', 'destroy']);
        Route::post('pengguna/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('pengguna/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');
        Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('index');
        Route::put('/profil', [PengaturanController::class, 'profil'])->name('profil');

        Route::post('/logo', [PengaturanController::class, 'logo'])->name('logo');
        Route::delete('/logo', [PengaturanController::class, 'hapusLogo'])->name('logo.hapus');
        Route::post('/qris', [PengaturanController::class, 'qris'])->name('qris');
        Route::delete('/qris', [PengaturanController::class, 'hapusQris'])->name('qris.hapus');

        Route::post('/backup', [BackupController::class, 'buat'])->name('backup.buat');
        Route::post('/backup/unggah', [BackupController::class, 'pulihkanUnggahan'])->name('backup.unggah');
        Route::get('/backup/{logBackup}/unduh', [BackupController::class, 'unduh'])->name('backup.unduh');
        Route::post('/backup/{logBackup}/restore', [BackupController::class, 'pulihkan'])->name('backup.restore');
        Route::delete('/backup/{logBackup}', [BackupController::class, 'hapus'])->name('backup.hapus');
    });
    });

    // ===== ADMIN dan STAFF GUDANG =====
    Route::middleware('role:admin,staff_gudang')->group(function () {
        Route::get('bahan-baku', [BahanBakuController::class, 'index'])->name('bahan-baku.index');
        Route::get('supplier', [SupplierController::class, 'index'])->name('supplier.index');

        Route::get('pergerakan-stok', [PergerakanStokController::class, 'index'])->name('pergerakan-stok.index');
        Route::get('penyesuaian-stok', [PenyesuaianStokController::class, 'index'])->name('penyesuaian-stok.index');
        Route::get('notifikasi/restock', [\App\Http\Controllers\NotifikasiController::class, 'restock'])->name('notifikasi.restock');

        Route::get('supplier/{supplier}', [SupplierController::class, 'show'])->name('supplier.show');
    });

    // ===== STAFF GUDANG =====
    Route::middleware('role:staff_gudang')->group(function () {
        Route::resource('barang-masuk', BarangMasukController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->parameters(['barang-masuk' => 'barangMasuk']);

        Route::get('bahan-rusak', [BahanRusakController::class, 'index'])->name('bahan-rusak.index');
        Route::post('bahan-rusak', [BahanRusakController::class, 'store'])->name('bahan-rusak.store');

        Route::get('penyesuaian-stok/create', [PenyesuaianStokController::class, 'create'])->name('penyesuaian-stok.create');
        Route::post('penyesuaian-stok', [PenyesuaianStokController::class, 'store'])->name('penyesuaian-stok.store');

        Route::get('stok-menipis', [StokMenipisController::class, 'index'])->name('stok-menipis.index');

        // Laporan bahan baku (fitur 58 dan 59)
        Route::get('laporan-bahan-baku', [LaporanController::class, 'persediaan'])->name('laporan.bahan-baku');
        Route::get('laporan-bahan-baku/penggunaan', [LaporanController::class, 'penggunaanBahan'])->name('laporan.bahan-baku.penggunaan');

        Route::middleware('role:staff_gudang')->group(function () {
});
    });

    // ===== KASIR =====
    Route::middleware('role:kasir')->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/pos', [KasirController::class, 'pos'])->name('pos');
        Route::post('/transaksi', [KasirController::class, 'proses'])->name('transaksi.store');

        Route::get('/riwayat', [TransaksiController::class, 'index'])->name('riwayat');
        Route::get('/riwayat/{transaksi}', [TransaksiController::class, 'show'])->name('riwayat.show');
        Route::post('/riwayat/{transaksi}/batal', [TransaksiController::class, 'batal'])->name('riwayat.batal');
        Route::get('/struk/{transaksi}', [TransaksiController::class, 'struk'])->name('struk');

        Route::get('/ketersediaan', [KetersediaanController::class, 'index'])->name('ketersediaan');
        Route::patch('/ketersediaan/{menu}/toggle', [KetersediaanController::class, 'toggle'])->name('ketersediaan.toggle');
        Route::post('/restock', [KetersediaanController::class, 'restock'])->name('restock');
        Route::get('/analitik', [AnalitikController::class, 'index'])->name('analitik');
        Route::get('/ketersediaan/status', [KetersediaanController::class, 'status'])->name('ketersediaan.status');
    });

    Route::get('notifikasi', [\App\Http\Controllers\NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('notifikasi/baca-semua', [\App\Http\Controllers\NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{notifikasi}/baca', [\App\Http\Controllers\NotifikasiController::class, 'baca'])->name('notifikasi.baca');
});