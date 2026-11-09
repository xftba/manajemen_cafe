<?php

// Menu sidebar per role.
// Item tunggal : label, route, aktif (pola nama route untuk highlight), icon
// Grup menu    : label, icon, anak (daftar item tanpa icon)

return [

    'admin' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'aktif' => 'dashboard',
         'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],

        ['label' => 'Pengguna', 'route' => 'users.index', 'aktif' => 'users.*',
         'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/>'],

        ['label' => 'Menu',
         'icon' => '<path d="M6 9h10v4a4 4 0 01-4 4h-2a4 4 0 01-4-4V9z"/><path d="M16 10h1a2 2 0 010 4h-1"/><path d="M9 4v2M13 4v2"/>',
         'anak' => [
             ['label' => 'Daftar Menu',    'route' => 'menu.index',     'aktif' => 'menu.*'],
             ['label' => 'Kategori Menu',  'route' => 'kategori.index', 'aktif' => 'kategori.*'],
             ['label' => 'Resep Menu',     'route' => 'resep.index',    'aktif' => 'resep.*'],
         ]],

        ['label' => 'Persediaan',
         'icon' => '<path d="M3 8l9-5 9 5v8l-9 5-9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
         'anak' => [
             ['label' => 'Data Bahan Baku',          'route' => 'bahan-baku.index',       'aktif' => 'bahan-baku.*'],
             ['label' => 'Data Supplier',            'route' => 'supplier.index',         'aktif' => 'supplier.*'],
             ['label' => 'Riwayat Pergerakan Stok',  'route' => 'pergerakan-stok.index',  'aktif' => 'pergerakan-stok.*'],
             ['label' => 'Penyesuaian Stok',         'route' => 'penyesuaian-stok.index', 'aktif' => 'penyesuaian-stok.*'],
         ]],

        ['label' => 'Riwayat Transaksi', 'route' => 'transaksi.index', 'aktif' => 'transaksi.*',
         'icon' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>'],

        ['label' => 'Laporan', 'route' => 'laporan.index', 'aktif' => 'laporan.*',
         'icon' => '<path d="M5 20v-6M12 20V6M19 20v-10M3 20h18"/>'],

        ['label' => 'Log Aktivitas', 'route' => 'log-aktivitas.index', 'aktif' => 'log-aktivitas.*',
         'icon' => '<path d="M3 12h4l3-7 4 14 3-7h4"/>'],

        ['label' => 'Pengaturan Cafe', 'route' => 'pengaturan.index', 'aktif' => 'pengaturan.*',
         'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
    ],

    'staff_gudang' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'aktif' => 'dashboard',
         'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],

        ['label' => 'Bahan Baku',
         'icon' => '<path d="M3 8l9-5 9 5v8l-9 5-9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
         'anak' => [
             ['label' => 'Data Bahan Baku',          'route' => 'bahan-baku.index', 'aktif' => 'bahan-baku.*'],
             ['label' => 'Peringatan Stok Menipis',  'route' => 'stok-menipis.index', 'aktif' => 'stok-menipis.*'],
         ]],

        ['label' => 'Data Supplier', 'route' => 'supplier.index', 'aktif' => 'supplier.*',
         'icon' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>'],

        ['label' => 'Barang Masuk', 'route' => 'barang-masuk.index', 'aktif' => 'barang-masuk.*',
         'icon' => '<path d="M21 8v8l-9 5-9-5V8l9-5 9 5z"/><path d="M12 12v4M10 14h4"/>'],

        ['label' => 'Pengelolaan Stok',
         'icon' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v2H9zM9 11h6M9 15h6"/>',
         'anak' => [
             ['label' => 'Stok Opname / Penyesuaian Stok', 'route' => 'penyesuaian-stok.index', 'aktif' => 'penyesuaian-stok.*'],
             ['label' => 'Bahan Rusak / Kadaluarsa',       'route' => 'bahan-rusak.index',      'aktif' => 'bahan-rusak.*'],
             ['label' => 'Riwayat Pergerakan Stok',        'route' => 'pergerakan-stok.index',  'aktif' => 'pergerakan-stok.*'],
         ]],

        ['label' => 'Laporan Bahan Baku', 'route' => 'laporan.bahan-baku', 'aktif' => 'laporan.bahan-baku*',
         'icon' => '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z"/><path d="M14 3v5h5M9 17v-3M12 17v-5M15 17v-2"/>'],
    ],

    // Kasir memakai sidebar ikon dengan tooltip (layouts/kasir.blade.php)
    'kasir' => [
        ['label' => 'Pos Kasir', 'route' => 'kasir.pos', 'aktif' => 'kasir.pos',
         'icon' => '<rect x="6" y="3" width="12" height="6" rx="1.5"/><rect x="3" y="12" width="18" height="9" rx="2"/><path d="M8 16h.01M12 16h.01M16 16h.01"/>'],

        ['label' => 'Riwayat Transaksi', 'route' => 'kasir.riwayat', 'aktif' => 'kasir.riwayat*',
         'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],

        ['label' => 'Persediaan Menu', 'route' => 'kasir.ketersediaan', 'aktif' => 'kasir.ketersediaan*',
         'icon' => '<path d="M3 19h18"/><path d="M5 19a7 7 0 0114 0"/><path d="M12 12V9.5M10.5 9.5h3"/>'],
    ],

];