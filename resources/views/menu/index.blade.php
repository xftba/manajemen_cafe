@extends('layouts.admin')

@section('title', 'Manajemen Menu')

@section('content')
@php
    $editId = old('_edit_id');

    $daftarMenu = $menu->getCollection()->map(fn ($m) => array_merge($m->ringkasan(), [
        'deskripsi'       => $m->deskripsi,
        'harga'           => (int) $m->harga,
        'pakai_varian'    => (bool) $m->pakai_varian,
        'tambahan_medium' => (int) $m->tambahan_medium,
        'tambahan_large'  => (int) $m->tambahan_large,
        'urlUpdate'       => route('menu.update', $m),
        'urlHapus'        => route('menu.destroy', $m),
    ]))->values();

    $lamaEdit = $editId ? [
        'nama'            => old('nama'),
        'deskripsi'       => old('deskripsi'),
        'kategori_id'     => old('kategori_id') ? (int) old('kategori_id') : null,
        'harga'           => old('harga'),
        'pakai_varian'    => (bool) old('pakai_varian'),
        'tambahan_medium' => old('tambahan_medium'),
        'tambahan_large'  => old('tambahan_large'),
    ] : null;

    // Supaya galat dan isian form Ubah tidak muncul di form Tambah
    $lama  = fn ($k, $default = null) => $editId ? $default : old($k, $default);
    $galat = fn ($k) => $editId ? null : $errors->first($k);

    $opsiKategori = $kategori->pluck('nama', 'id')->all();

    $ikonTotal    = '<path d="M6 9h10v4a4 4 0 01-4 4h-2a4 4 0 01-4-4V9z"/><path d="M16 10h1a2 2 0 010 4h-1"/><path d="M9 4v2M13 4v2"/>';
    $ikonAktif    = '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>';
    $ikonNonaktif = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $kartu = [
        ['Total menu', $ringkas['total'],                        $ikonTotal,    'bg-cream text-accent',            'text-neutral-900'],
        ['Tersedia',   $ringkas['tersedia'],                     $ikonAktif,    'bg-green-100 text-green-700',     'text-green-700'],
        ['Habis',      $ringkas['total'] - $ringkas['tersedia'], $ikonNonaktif, 'bg-neutral-200 text-neutral-500', 'text-neutral-500'],
    ];

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
    $kelasPopup = 'mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent';
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="editMenu(@js($daftarMenu), @js($editId ? (int) $editId : null), @js($lamaEdit), @js($opsiKategori), @js([
         'urlStatus' => route('menu.status'),
     ]))"
     @keydown.escape.window="tutup()">

    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_300px]">

        {{-- ================= KIRI: DAFTAR MENU ================= --}}
        <section class="min-w-0">

            {{-- Ringkasan --}}
            <div class="mb-3 grid grid-cols-3 gap-3">
                @foreach ($kartu as [$judul, $nilai, $ikon, $warnaIkon, $warnaAngka])
                    <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] text-neutral-500">{{ $judul }}</p>
                            <p class="text-xl font-semibold leading-tight {{ $warnaAngka }}">{{ $nilai }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pencarian dan filter --}}
            <form method="GET" action="{{ route('menu.index') }}" x-data
                  class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari nama menu..." autocomplete="off"
                           x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                           @input.debounce.500ms="$el.form.submit()"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                <x-pilihan name="kategori"
                           :options="$opsiKategori"
                           :selected="request('kategori')"
                           semua="Semua kategori" />
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">

                <div class="hidden items-center bg-cream px-4 py-2.5 text-[11px] font-semibold text-accent lg:grid lg:grid-cols-[minmax(0,2.5fr)_1.2fr_1fr_1fr_80px]">
                    <span>Menu &amp; Kategori</span>
                    <span class="text-center">Harga</span>
                    <span class="text-center">Estimasi</span>
                    <span class="text-center">Status</span>
                    <span class="text-center">Aksi</span>
                </div>

                <template x-for="m in daftar" :key="m.id">
                    <div class="grid gap-2 border-t border-neutral-100 px-4 py-1.5 first:border-t-0 lg:grid-cols-[minmax(0,2.5fr)_1.2fr_1fr_1fr_80px] lg:items-center">

                        {{-- Menu dan kategori --}}
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white">
                                <img x-show="m.gambar_url" :src="m.gambar_url" :alt="m.nama"
                                     class="h-full w-full object-contain" :class="m.status === 'habis' ? 'opacity-50 grayscale' : ''">
                                <span x-show="!m.gambar_url" class="text-base font-semibold text-neutral-300" x-text="m.nama.charAt(0)"></span>

                                <span x-show="m.status === 'habis'"
                                      class="absolute inset-0 flex items-center justify-center bg-neutral-500/50 text-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/></svg>
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="text-[11px] font-semibold"
                                       :class="m.status === 'habis' ? 'text-neutral-400 line-through' : ''" x-text="m.nama"></p>
                                    <span class="rounded-full bg-cream px-2 py-px text-[9px] font-medium text-accent" x-text="m.kategori"></span>
                                </div>
                                <p class="mt-0.5 truncate text-[9px]" :class="kelasCatatan(m)" x-text="m.catatan"></p>
                            </div>
                        </div>

                        {{-- Harga --}}
                        <p class="text-[11px] font-semibold lg:text-center" x-text="harga(m)"></p>

                        {{-- Estimasi --}}
                        <p class="text-[11px] font-semibold lg:text-center"
                           :class="m.porsi === 0 ? 'text-red-600' : ''" x-text="m.porsi + ' Porsi'"></p>

                        {{-- Indikator status (otomatis dari stok dan resep) --}}
                        <div class="flex items-center gap-3 lg:justify-center">
                            <span role="img" :aria-label="m.tersedia ? 'Tersedia' : 'Habis'"
                                  :title="m.tersedia ? 'Tersedia' : 'Habis'"
                                  :class="m.tersedia ? 'bg-accent' : 'bg-neutral-200'"
                                  class="relative inline-flex h-6 w-10 shrink-0 items-center rounded-full transition">
                                <span :class="m.tersedia ? 'translate-x-5' : 'translate-x-1'"
                                      class="inline-block h-4 w-4 rounded-full bg-white shadow transition"></span>
                            </span>
                        </div>

                        {{-- Aksi (khusus admin) --}}
                        <div class="flex items-center gap-1.5 lg:justify-center">
                            <button type="button" @click="buka(m.id)" title="Ubah menu" :aria-label="'Ubah menu ' + m.nama"
                                    class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>

                            <form method="POST" :action="m.urlHapus"
                                  @submit="if (!confirm('Hapus menu ' + m.nama + '?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus menu" :aria-label="'Hapus menu ' + m.nama"
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </template>

                <p x-show="daftar.length === 0" class="px-4 py-12 text-center text-xs text-neutral-400">Belum ada menu yang cocok.</p>
            </div>

            <div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $menu->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: FORM TAMBAH MENU ================= --}}
        <aside class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px]">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </span>
                <h2 class="text-[13px] font-semibold leading-tight">Tambah Menu</h2>
            </div>

            <form method="POST" action="{{ route('menu.store') }}" enctype="multipart/form-data"
                  x-data="{
                      preview: null,
                      varian: @js((bool) $lama('pakai_varian', false)),
                      deskripsi: @js((string) $lama('deskripsi', '')),
                  }"
                  class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                @csrf

                {{-- Foto + Nama sebaris --}}
                <div class="flex items-end gap-2">
                    <label title="Pilih foto (JPG, PNG, WebP, maks. 2 MB)"
                           class="flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-neutral-300 bg-surface transition hover:border-accent">
                        <img x-show="preview" :src="preview" alt="Pratinjau" class="h-full w-full object-cover">
                        <svg x-show="!preview" class="h-5 w-5 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 8"/>
                        </svg>
                        <input type="file" name="gambar" accept="image/png,image/jpeg,image/webp" class="hidden"
                               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    </label>
                    <div class="min-w-0 flex-1">
                        <label class="text-[11px] font-medium text-neutral-700">Nama menu</label>
                        <input type="text" name="nama" value="{{ $lama('nama') }}" required maxlength="100"
                               placeholder="Contoh: Caramel Latte" class="{{ $kelasInput }}">
                    </div>
                </div>
                @if ($galat('gambar')) <p class="text-[10px] text-red-600">{{ $galat('gambar') }}</p> @endif
                @if ($galat('nama'))   <p class="text-[10px] text-red-600">{{ $galat('nama') }}</p> @endif

                {{-- Deskripsi --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-medium text-neutral-700">Deskripsi</label>
                        <span class="text-[10px] text-neutral-400" x-text="deskripsi.length + '/70'"></span>
                    </div>
                    <textarea name="deskripsi" rows="2" x-model="deskripsi" maxlength="70"
                              placeholder="Deskripsi singkat (tampil di kasir)"
                              class="mt-0.5 w-full resize-none rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                    @if ($galat('deskripsi')) <p class="text-[10px] text-red-600">{{ $galat('deskripsi') }}</p> @endif
                </div>

                {{-- Kategori + Harga sebaris --}}
                <div class="grid grid-cols-2 items-end gap-2">
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Kategori</label>
                        <x-pilihan name="kategori_id"
                                :options="$opsiKategori"
                                :selected="$lama('kategori_id')"
                                :semua="null"
                                placeholder="Pilih"
                                :auto="false" />
                    </div>
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Harga / Small (Rp)</label>
                        <input type="number" name="harga" min="0" step="500" value="{{ $lama('harga') }}" required
                               placeholder="0"
                               class="mt-1.5 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>

                {{-- Varian --}}
                <div class="rounded-xl bg-cream/60 px-3 py-2">
                    <input type="hidden" name="pakai_varian" :value="varian ? 1 : 0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-medium text-neutral-700">Punya pilihan varian</p>
                            <p class="text-[10px] leading-snug text-neutral-500">Temperature, size, sugar level.</p>
                        </div>
                        <button type="button" role="switch" :aria-checked="varian" @click="varian = !varian"
                                :class="varian ? 'bg-accent' : 'bg-neutral-300'"
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition">
                            <span :class="varian ? 'translate-x-[18px]' : 'translate-x-0.5'"
                                  class="inline-block h-4 w-4 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>

                    <div x-show="varian" x-cloak class="mt-2 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] text-neutral-600">+ Medium</label>
                            <input type="number" name="tambahan_medium" min="0" step="500"
                                   value="{{ $lama('tambahan_medium', 0) }}" class="{{ $kelasInput }}">
                        </div>
                        <div>
                            <label class="text-[10px] text-neutral-600">+ Large</label>
                            <input type="number" name="tambahan_large" min="0" step="500"
                                   value="{{ $lama('tambahan_large', 0) }}" class="{{ $kelasInput }}">
                        </div>
                    </div>
                    @if ($galat('tambahan_medium') || $galat('tambahan_large'))
                        <p class="mt-1 text-[10px] text-red-600">{{ $galat('tambahan_medium') ?: $galat('tambahan_large') }}</p>
                    @endif
                </div>

                <p class="text-[10px] leading-snug text-neutral-500">
                    Status tersedia/habis otomatis, mengikuti resep dan stok bahan.
                </p>

                <button type="submit"
                        class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                    Simpan Menu
                </button>
            </form>
        </aside>
    </div>

    {{-- ================= POPUP UBAH MENU ================= --}}
    <div x-show="id !== null" x-cloak @click.self="tutup()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

        <template x-if="m">
            <div class="max-h-[90vh] w-full max-w-[400px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">

                {{-- Informasi menu --}}
                <div class="flex items-center gap-3 border-b border-neutral-200 pb-3">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface">
                        <img x-show="f.preview" :src="f.preview" :alt="m.nama" class="h-full w-full object-cover">
                        <span x-show="!f.preview" class="text-2xl font-semibold text-accent" x-text="m.nama.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="truncate text-[13px] font-semibold" x-text="m.nama"></h3>
                        <p class="mt-0.5 text-[11px] text-neutral-500" x-text="namaKategori(m.kategori_id)"></p>
                        <label class="mt-1 inline-flex h-7 cursor-pointer items-center rounded-full border border-accent px-3 text-[10px] font-semibold text-accent transition hover:bg-accent/5">
                            Ganti foto
                            <input type="file" name="gambar" form="form-ubah-menu" accept="image/png,image/jpeg,image/webp" class="hidden"
                                   @change="f.preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : m.gambar_url">
                        </label>
                    </div>
                </div>

                @if ($errors->has('gambar') && $editId)
                    <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('gambar') }}</p>
                @endif

                {{-- Form --}}
                <form id="form-ubah-menu" method="POST" enctype="multipart/form-data" :action="m.urlUpdate">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_edit_id" :value="m.id">

                    <label class="mt-3 block text-[11px] font-semibold">Nama menu</label>
                    <input type="text" name="nama" x-model="f.nama" required maxlength="100" class="{{ $kelasPopup }}">
                    @if ($errors->has('nama') && $editId)
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('nama') }}</p>
                    @endif

                    {{-- Deskripsi --}}
                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-[11px] font-semibold">Deskripsi</p>
                        <p class="text-[9px] text-neutral-400" x-text="(f.deskripsi || '').length + '/70'"></p>
                    </div>
                    <textarea name="deskripsi" x-model="f.deskripsi" maxlength="70" rows="2"
                              placeholder="Deskripsi singkat menu"
                              class="mt-1.5 w-full resize-none rounded-lg border-0 bg-surface px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent"></textarea>
                    @if ($errors->has('deskripsi') && $editId)
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('deskripsi') }}</p>
                    @endif

                    {{-- Kategori + Harga sebaris --}}
                    <div class="mt-3 grid grid-cols-2 items-start gap-2">

                        {{-- Kategori --}}
                        <div>
                            <p class="text-[11px] font-semibold">Kategori</p>
                            <input type="hidden" name="kategori_id" :value="f.kategori_id">
                            <div class="relative" @click.outside="bukaKat = false">
                                <button type="button" @click="bukaKat = !bukaKat"
                                        class="mt-1.5 flex h-9 w-full items-center justify-between gap-1 rounded-lg bg-surface px-3 text-left text-[11px] transition"
                                        :class="bukaKat ? 'ring-2 ring-accent' : ''">
                                    <span class="truncate" x-text="namaKategori(f.kategori_id) || 'Pilih'"></span>
                                    <svg class="h-3.5 w-3.5 shrink-0 text-neutral-500 transition-transform" :class="bukaKat ? 'rotate-180' : ''"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div x-show="bukaKat" x-cloak x-transition.origin.top
                                     class="absolute left-0 top-full z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">
                                    <template x-for="[kid, knama] in Object.entries(kategori)" :key="kid">
                                        <button type="button" @click="f.kategori_id = Number(kid); bukaKat = false"
                                                :class="f.kategori_id === Number(kid) ? 'bg-cream font-semibold text-accent' : 'text-neutral-700 hover:bg-cream/60'"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[11px] transition">
                                            <span x-text="knama"></span>
                                            <svg x-show="f.kategori_id === Number(kid)" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            @if ($errors->has('kategori_id') && $editId)
                                <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('kategori_id') }}</p>
                            @endif
                        </div>

                        {{-- Harga --}}
                        <div>
                            <label class="block text-[11px] font-semibold">Harga / Small (Rp)</label>
                            <input type="number" name="harga" x-model="f.harga" min="0" step="500" required class="{{ $kelasPopup }}">
                            @if ($errors->has('harga') && $editId)
                                <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('harga') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Varian --}}
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold">Punya pilihan varian</p>
                            <p class="text-[10px] text-neutral-500">Temperature, size, dan sugar level.</p>
                        </div>
                        <input type="hidden" name="pakai_varian" :value="f.pakai_varian ? 1 : 0">
                        <button type="button" role="switch" :aria-checked="f.pakai_varian" @click="f.pakai_varian = !f.pakai_varian"
                                :class="f.pakai_varian ? 'bg-accent' : 'bg-neutral-300'"
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition">
                            <span :class="f.pakai_varian ? 'translate-x-[18px]' : 'translate-x-0.5'"
                                  class="inline-block h-4 w-4 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>

                    <div x-show="f.pakai_varian" x-cloak class="mt-2 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] text-neutral-600">Tambahan Medium</label>
                            <input type="number" name="tambahan_medium" x-model="f.tambahan_medium" min="0" step="500" class="{{ $kelasPopup }}">
                        </div>
                        <div>
                            <label class="text-[10px] text-neutral-600">Tambahan Large</label>
                            <input type="number" name="tambahan_large" x-model="f.tambahan_large" min="0" step="500" class="{{ $kelasPopup }}">
                        </div>
                    </div>
                    @if (($errors->has('tambahan_medium') || $errors->has('tambahan_large')) && $editId)
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('tambahan_medium') ?: $errors->first('tambahan_large') }}</p>
                    @endif

                    {{-- Tombol aksi --}}
                    <div class="mt-4 grid grid-cols-[80px_1fr] gap-2 border-t border-neutral-200 pt-3">
                        <button type="button" @click="tutup()"
                                class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90">
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editMenu', (daftar, bukaId, lama, kategori, konfig) => ({
        daftar,
        bukaId,
        kategori,
        konfig,
        id: null,
        bukaKat: false,
        f: {},

        get m() { return this.daftar.find(x => x.id === this.id) || null; },

        namaKategori(kid) { return this.kategori[kid] || ''; },

        rupiah(n) { return 'Rp ' + Math.round(Number(n)).toLocaleString('id-ID'); },
        harga(m) {
            return m.harga_min === m.harga_maks
                ? this.rupiah(m.harga_min)
                : this.rupiah(m.harga_min) + ' - ' + this.rupiah(m.harga_maks);
        },
        kelasCatatan(m) {
            return {
                bahaya: 'text-red-600',
                info: 'text-blue-700',
                peringatan: 'text-amber-700',
                netral: 'text-neutral-400',
            }[m.tipe_catatan];
        },

        buka(id) {
            const m = this.daftar.find(x => x.id === id);
            if (!m) return;
            this.f = {
                nama: m.nama,
                // dipotong 50 karakter supaya counter dan maxlength konsisten
                deskripsi: (m.deskripsi || '').slice(0, 50),
                kategori_id: m.kategori_id,
                harga: m.harga,
                pakai_varian: m.pakai_varian,
                tambahan_medium: m.tambahan_medium,
                tambahan_large: m.tambahan_large,
                preview: m.gambar_url,
            };
            this.bukaKat = false;
            this.id = id;
        },
        tutup() { this.id = null; },

        // Ambil status terbaru (stok berubah, resep berubah, dsb.)
        async segarkan() {
            if (document.hidden || this.id !== null) return;
            try {
                const ids = this.daftar.map(x => x.id).join(',');
                const res = await fetch(this.konfig.urlStatus + '?ids=' + ids, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.daftar.forEach(m => {
                    if (data[m.id]) Object.assign(m, data[m.id]);
                });
            } catch (e) {}
        },

        init() {
            setInterval(() => this.segarkan(), 5000);

            if (!this.bukaId) return;
            this.buka(this.bukaId);
            // Kalau validasi gagal, pakai isian terakhir pengguna
            if (lama) {
                for (const k of Object.keys(lama)) {
                    if (lama[k] !== null && lama[k] !== undefined) this.f[k] = lama[k];
                }
            }
        },
    }));
});
</script>
@endpush