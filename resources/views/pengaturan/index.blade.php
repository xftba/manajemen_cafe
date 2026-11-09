@extends('layouts.admin')

@section('title', 'Pengaturan Café')

@section('content')
@php
    $ukuran = fn ($b) => $b >= 1048576
        ? number_format($b / 1048576, 2, ',', '.') . ' MB'
        : number_format($b / 1024, 1, ',', '.') . ' KB';

    $warnaTipe = [
        'backup'      => ['bg-green-100 text-green-800',   'bg-green-600'],
        'pra-restore' => ['bg-amber-100 text-amber-800',   'bg-amber-600'],
        'restore'     => ['bg-purple-100 text-purple-800', 'bg-purple-600'],
    ];
    $warnaDefault = ['bg-neutral-100 text-neutral-700', 'bg-neutral-500'];

    $logoUrl = $pengaturan->logo ? asset('storage/' . $pengaturan->logo) : null;
    $qrisUrl = $pengaturan->qris_gambar ? asset('storage/' . $pengaturan->qris_gambar) : null;

    $tabs = [
        'profil' => 'Profil',
        'logo'   => 'Logo',
        'qris'   => 'QRIS',
        'backup' => 'Backup & Restore',
    ];

    $kelasInput = 'mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent';

    $ikonRiwayat = '<path d="M4 12a8 8 0 108-8 8 8 0 00-5.7 2.3L4 8"/><path d="M4 4v4h4M12 8v4l3 2"/>';
@endphp

<div class="mx-auto max-w-[1400px]" x-data="{ tab: @js($tab) }">

    {{-- Tab --}}
    <nav class="no-scrollbar -mx-1 mb-3 flex flex-nowrap items-center gap-2 overflow-x-auto px-1 py-1">
        @foreach ($tabs as $kode => $nama)
            <button type="button" @click="tab = '{{ $kode }}'"
                    :class="tab === '{{ $kode }}' ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-500 hover:bg-neutral-300/70'"
                    class="flex h-9 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-[11px] font-semibold transition">
                {{ $nama }}
            </button>
        @endforeach
    </nav>

    {{-- ================= PROFIL ================= --}}
    <div x-show="tab === 'profil'" x-cloak
         x-data="{
            nama: @js(old('nama_cafe', $pengaturan->nama_cafe)),
            alamat: @js(old('alamat', $pengaturan->alamat)),
            telepon: @js(old('telepon', $pengaturan->telepon))
         }">
        <div class="grid items-start gap-3 lg:grid-cols-2">

            {{-- Form --}}
            <form method="POST" action="{{ route('pengaturan.profil') }}" class="rounded-2xl bg-white p-4 shadow-sm">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 10l9-6 9 6v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/><path d="M9 21v-7h6v7"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-semibold leading-tight">Profil café</h2>
                        <p class="text-[10px] text-neutral-500">Tampil di sidebar, halaman login, struk, dan kop laporan.</p>
                    </div>
                </div>

                <label class="mt-3 block text-[11px] font-semibold">Nama café</label>
                <input type="text" name="nama_cafe" x-model="nama" required maxlength="100" class="{{ $kelasInput }}">
                @error('nama_cafe') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror

                <label class="mt-3 block text-[11px] font-semibold">Alamat</label>
                <textarea name="alamat" rows="3" maxlength="255" x-model="alamat"
                          class="mt-1.5 w-full resize-none rounded-lg border-0 bg-surface px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent"></textarea>
                @error('alamat') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror

                <label class="mt-3 block text-[11px] font-semibold">Telepon</label>
                <input type="text" name="telepon" x-model="telepon" maxlength="20" class="{{ $kelasInput }}">
                @error('telepon') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror

                <button type="submit"
                        class="mt-4 h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                    Simpan
                </button>
            </form>

            {{-- Pratinjau struk --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-semibold leading-tight">Pratinjau struk</h2>
                <p class="text-[10px] text-neutral-500">Begini profil café tampil di struk dan kop laporan.</p>

                <div class="mx-auto mt-3 w-full max-w-[280px] rounded-lg bg-surface px-5 py-4 text-center text-[11px] text-neutral-700">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="mx-auto mb-2 h-12 w-12 object-contain">
                    @endif
                    <p class="text-[13px] font-bold text-neutral-900" x-text="nama || 'Nama café'"></p>
                    <p class="mt-0.5 whitespace-pre-line text-[10px] text-neutral-500" x-text="alamat || 'Alamat café'"></p>
                    <p class="text-[10px] text-neutral-500" x-text="telepon || 'Telepon'"></p>

                    <div class="my-3 border-t border-dashed border-neutral-300"></div>

                    <div class="space-y-1 text-left text-[10px]">
                        <div class="flex justify-between"><span>2x Kopi Susu</span><span>Rp 36.000</span></div>
                        <div class="flex justify-between"><span>1x Croissant</span><span>Rp 22.000</span></div>
                    </div>

                    <div class="my-3 border-t border-dashed border-neutral-300"></div>

                    <div class="flex justify-between text-[11px] font-bold text-neutral-900">
                        <span>Total</span><span>Rp 58.000</span>
                    </div>
                    <p class="mt-3 text-[10px] text-neutral-400">Terima kasih atas kunjungan Anda</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= LOGO ================= --}}
    <div x-show="tab === 'logo'" x-cloak x-data="{ preview: @js($logoUrl), nama: '' }">
        <div class="grid items-start gap-3 lg:grid-cols-2">

            {{-- Unggah --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 8"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-semibold leading-tight">Logo</h2>
                        <p class="text-[10px] text-neutral-500">Gunakan gambar persegi dengan latar transparan atau putih.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('pengaturan.logo') }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <div class="mx-auto flex h-52 w-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-neutral-300 bg-surface">
                        <img x-show="preview" :src="preview" alt="Pratinjau logo" class="h-full w-full object-contain p-2">
                        <span x-show="!preview" class="px-3 text-center text-[10px] text-neutral-400">Belum ada logo</span>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <label class="inline-flex h-8 shrink-0 cursor-pointer items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                            Pilih gambar
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="hidden"
                                   @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); nama = f.name }">
                        </label>
                        <div class="min-w-0">
                            <p class="truncate text-[10px] text-neutral-600" x-show="nama" x-text="nama"></p>
                            <p class="text-[10px] text-neutral-400">JPG, PNG, atau WebP. Maks. 2 MB.</p>
                        </div>
                    </div>
                    @error('logo') <p class="mt-2 text-[10px] text-red-600">{{ $message }}</p> @enderror

                    <button type="submit"
                            class="mt-4 h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                        Simpan Logo
                    </button>
                </form>

                @if ($logoUrl)
                    <form method="POST" action="{{ route('pengaturan.logo.hapus') }}" class="mt-2"
                          onsubmit="return confirm('Hapus logo café?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="h-9 w-full rounded-full bg-red-100 text-[11px] font-semibold text-red-600 transition hover:bg-red-200">
                            Hapus logo
                        </button>
                    </form>
                @endif
            </div>

            {{-- Pratinjau penggunaan --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-semibold leading-tight">Pratinjau tampilan</h2>
                <p class="text-[10px] text-neutral-500">Logo akan tampil seperti ini di aplikasi.</p>

                <p class="mt-3 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">Sidebar</p>
                <div class="mt-1.5 flex items-center gap-2 rounded-xl bg-surface px-3 py-3">
                    <img x-show="preview" :src="preview" alt="" class="h-9 w-9 rounded-full object-contain">
                    <span x-show="!preview" class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-[9px] text-neutral-400">Logo</span>
                    <span class="text-[13px] font-semibold">{{ $pengaturan->nama_cafe }}</span>
                </div>

                <p class="mt-4 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">Halaman login</p>
                <div class="mt-1.5 flex flex-col items-center rounded-xl bg-surface px-3 py-6 text-center">
                    <img x-show="preview" :src="preview" alt="" class="h-16 w-16 object-contain">
                    <span x-show="!preview" class="flex h-16 w-16 items-center justify-center rounded-full bg-neutral-200 text-[10px] text-neutral-400">Logo</span>
                    <p class="mt-2 text-[13px] font-semibold">{{ $pengaturan->nama_cafe }}</p>
                    <p class="text-[10px] text-neutral-500">Masuk ke akun Anda</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= QRIS ================= --}}
    <div x-show="tab === 'qris'" x-cloak x-data="{ preview: @js($qrisUrl), nama: '' }">
        <div class="grid items-start gap-3 lg:grid-cols-2">

            {{-- Unggah --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h3M21 17v4"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-semibold leading-tight">QRIS</h2>
                        <p class="text-[10px] text-neutral-500">Tampil di Pos Kasir saat pelanggan membayar dengan QRIS.</p>
                    </div>
                </div>

                <p class="mt-3 text-[10px] leading-snug text-neutral-500">
                    Unggah gambar QRIS statis café Anda, dan pastikan hasil pindai menuju rekening yang benar.
                </p>

                <form method="POST" action="{{ route('pengaturan.qris') }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <div class="flex items-center gap-3">
                        <label class="inline-flex h-8 shrink-0 cursor-pointer items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                            Pilih gambar
                            <input type="file" name="qris" accept="image/png,image/jpeg,image/webp" required class="hidden"
                                   @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); nama = f.name }">
                        </label>
                        <div class="min-w-0">
                            <p class="truncate text-[10px] text-neutral-600" x-show="nama" x-text="nama"></p>
                            <p class="text-[10px] text-neutral-400">JPG, PNG, atau WebP. Maks. 2 MB.</p>
                        </div>
                    </div>
                    @error('qris') <p class="mt-2 text-[10px] text-red-600">{{ $message }}</p> @enderror

                    <button type="submit"
                            class="mt-4 h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                        Simpan QRIS
                    </button>
                </form>

                @if ($qrisUrl)
                    <form method="POST" action="{{ route('pengaturan.qris.hapus') }}" class="mt-2"
                          onsubmit="return confirm('Hapus gambar QRIS?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="h-9 w-full rounded-full bg-red-100 text-[11px] font-semibold text-red-600 transition hover:bg-red-200">
                            Hapus gambar QRIS
                        </button>
                    </form>
                @endif
            </div>

            {{-- Pratinjau --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-semibold leading-tight">Pratinjau di Pos Kasir</h2>
                <p class="text-[10px] text-neutral-500">Pelanggan akan memindai gambar ini saat membayar.</p>

                <div class="mx-auto mt-3 flex h-80 w-full max-w-[320px] items-center justify-center overflow-hidden rounded-xl border border-dashed border-neutral-300 bg-surface">
                    <img x-show="preview" :src="preview" alt="Pratinjau QRIS" class="h-full w-full object-contain p-3">
                    <span x-show="!preview" class="px-3 text-center text-[10px] text-neutral-400">Belum ada gambar QRIS</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= BACKUP & RESTORE ================= --}}
    <div x-show="tab === 'backup'" x-cloak
         x-data="{ restore: null, setuju: false, pw: '', proses: false, buat: false, berkas: '' }"
         @keydown.escape.window="if (!proses) restore = null">

        @if ($errors->get('berkas') || $errors->get('password'))
            <div class="mb-3 rounded-lg bg-red-100 px-3 py-2 text-xs text-red-800">
                <ul class="list-inside list-disc">
                    @foreach (array_merge($errors->get('berkas'), $errors->get('password')) as $pesan) <li>{{ $pesan }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3 grid items-start gap-3 lg:grid-cols-[minmax(0,1fr)_320px]">

            {{-- Backup --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-semibold leading-tight">Backup database</h2>
                        <p class="text-[10px] text-neutral-500">
                            Backup terakhir:
                            <strong class="font-semibold text-neutral-800">{{ $terakhir ? $terakhir->created_at->translatedFormat('d F Y H:i') : 'belum pernah' }}</strong>
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-[11px] leading-relaxed text-neutral-600">
                    Menyimpan salinan seluruh data aplikasi: pengguna, menu, resep, bahan baku, supplier, transaksi, stok, log aktivitas, dan pengaturan.
                    Sesi login, cache, dan riwayat backup ini tidak ikut disimpan.
                </p>

                <form method="POST" action="{{ route('pengaturan.backup.buat') }}" class="mt-3" @submit="buat = true">
                    @csrf
                    <button :disabled="buat"
                            class="inline-flex h-9 items-center gap-1.5 rounded-full bg-accent pl-3 pr-4 text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:opacity-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        <span x-text="buat ? 'Membuat backup...' : 'Buat Backup Sekarang'"></span>
                    </button>
                </form>

                <p class="mt-3 text-[10px] leading-snug text-neutral-500">
                    Berkas disimpan di server pada <code class="rounded bg-surface px-1 py-px">storage/app/private/backup</code>.
                    Unduh dan simpan salinannya di tempat lain (flashdisk atau cloud), karena backup yang hanya ada di komputer yang sama tidak aman kalau komputernya rusak.
                </p>
            </div>

            {{-- Restore dari berkas --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 16V4M7 9l5-5 5 5M5 20h14"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-semibold leading-tight">Restore dari berkas</h2>
                        <p class="text-[10px] text-neutral-500">Hanya berkas buatan aplikasi ini yang belum diubah.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('pengaturan.backup.unggah') }}" enctype="multipart/form-data"
                      class="mt-3 border-t border-neutral-200 pt-3"
                      onsubmit="return confirm('Semua data saat ini akan DIGANTI dengan isi berkas ini. Lanjutkan?')">
                    @csrf

                    <div class="flex items-center gap-3">
                        <label class="inline-flex h-8 shrink-0 cursor-pointer items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                            Pilih berkas
                            <input type="file" name="berkas" accept=".sql" required class="hidden"
                                   @change="berkas = $event.target.files[0] ? $event.target.files[0].name : ''">
                        </label>
                        <p class="min-w-0 truncate text-[10px] text-neutral-500" x-text="berkas || 'Belum ada berkas (.sql)'"></p>
                    </div>

                    <label class="mt-3 block text-[11px] font-semibold">Password Anda</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           placeholder="Masukkan password" class="{{ $kelasInput }}">

                    <button type="submit"
                            class="mt-3 h-9 w-full rounded-full bg-red-600 text-[12px] font-semibold text-white shadow-sm transition hover:bg-red-700 active:scale-[0.99]">
                        Pulihkan dari Berkas
                    </button>
                </form>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Waktu</th>
                            <th class="px-4 py-2.5 font-semibold">Jenis</th>
                            <th class="px-4 py-2.5 font-semibold">Berkas</th>
                            <th class="px-4 py-2.5 font-semibold">Ukuran</th>
                            <th class="px-4 py-2.5 font-semibold">Petugas</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $item)
                            @php [$kelas, $titik] = $warnaTipe[$item->tipe] ?? $warnaDefault; @endphp
                            <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">

                                <td class="whitespace-nowrap px-4 py-2">
                                    <p class="font-semibold">{{ $item->created_at->format('d/m/Y') }}</p>
                                    <p class="text-[10px] text-neutral-400">{{ $item->created_at->format('H:i:s') }}</p>
                                </td>

                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $kelas }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $titik }}"></span>
                                        {{ \App\Models\LogBackup::TIPE[$item->tipe] ?? $item->tipe }}
                                    </span>
                                </td>

                                <td class="max-w-[280px] break-all px-4 py-2 text-neutral-700">{{ $item->nama_file }}</td>

                                <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ $item->ukuran ? $ukuran($item->ukuran) : '-' }}</td>

                                <td class="px-4 py-2">{{ $item->user?->nama ?? 'Akun tidak ada' }}</td>

                                <td class="px-4 py-2">
                                    @if ($item->file_ada)
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- Unduh --}}
                                            <a href="{{ route('pengaturan.backup.unduh', $item) }}" title="Unduh" aria-label="Unduh {{ $item->nama_file }}"
                                               class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>
                                                </svg>
                                            </a>

                                            {{-- Pulihkan --}}
                                            <button type="button" title="Pulihkan" aria-label="Pulihkan dari {{ $item->nama_file }}"
                                                    @click="restore = { url: @js(route('pengaturan.backup.restore', $item)), nama: @js($item->nama_file) }; setuju = false; pw = ''; proses = false"
                                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700 transition hover:bg-amber-200">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 12a9 9 0 0115-6.7L21 8M21 3v5h-5M21 12a9 9 0 01-15 6.7L3 16M3 21v-5h5"/>
                                                </svg>
                                            </button>

                                            {{-- Hapus --}}
                                            <form method="POST" action="{{ route('pengaturan.backup.hapus', $item) }}"
                                                  onsubmit="return confirm('Hapus berkas backup ini dari server?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" aria-label="Hapus {{ $item->nama_file }}"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($item->tipe === 'restore')
                                        <p class="text-right text-neutral-300">-</p>
                                    @else
                                        <p class="text-right text-[10px] text-neutral-400">Berkas tidak tersedia</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonRiwayat !!}</svg>
                                    <p class="mt-2 text-xs text-neutral-400">Belum ada riwayat backup.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $riwayat->withQueryString()->links('vendor.pagination.custom') }}</div>

        {{-- ================= POPUP PULIHKAN ================= --}}
        <div x-show="restore" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4">
            <form method="POST" :action="restore ? restore.url : ''" @submit="proses = true"
                  class="max-h-[90vh] w-full max-w-[340px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">
                @csrf

                <div class="flex items-center gap-3 border-b border-neutral-200 pb-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold">Pulihkan database</h3>
                        <p class="truncate text-[11px] text-neutral-500" x-text="restore ? restore.nama : ''"></p>
                    </div>
                </div>

                <p class="mt-3 text-[11px] leading-relaxed text-neutral-600">
                    Seluruh data saat ini akan <strong>diganti</strong> dengan isi berkas ini. Transaksi, stok, dan perubahan lain setelah backup itu dibuat akan hilang.
                </p>
                <p class="mt-1.5 text-[10px] leading-snug text-neutral-500">
                    Sistem otomatis membuat backup pengaman sebelum memulihkan, sehingga kondisi sekarang masih bisa dikembalikan.
                </p>

                <label class="mt-3 block text-[11px] font-semibold">Password Anda</label>
                <input type="password" name="password" x-model="pw" required autocomplete="current-password"
                       placeholder="Masukkan password" class="{{ $kelasInput }}">

                <label class="mt-3 flex items-start gap-2 text-[11px] text-neutral-700">
                    <input type="checkbox" x-model="setuju" class="mt-0.5 h-3.5 w-3.5 rounded">
                    Saya mengerti bahwa data saat ini akan diganti.
                </label>

                <div class="mt-4 grid grid-cols-[80px_1fr] gap-2">
                    <button type="button" @click="restore = null" :disabled="proses"
                            class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5 disabled:opacity-50">
                        Batal
                    </button>
                    <button type="submit" :disabled="!setuju || !pw || proses"
                            class="h-9 rounded-lg bg-red-600 text-[11px] font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                            x-text="proses ? 'Memulihkan...' : 'Pulihkan'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection