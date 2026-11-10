@extends('layouts.admin')

@section('title', 'Data Bahan Baku')

@section('content')
@php
    $admin = auth()->user()->role === 'admin';
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);
    $editId = old('_edit_id');

    $daftarBahan = $bahan->getCollection()->map(fn ($b) => [
        'id'           => $b->id,
        'nama'         => $b->nama,
        'satuan'       => $b->satuan,
        'stok_teks'    => $angka($b->stok) . ' ' . $b->satuan,
        'stok_minimum' => (float) $b->stok_minimum,
        'urlUpdate'    => route('bahan-baku.update', $b),
    ])->values();

    $total   = \App\Models\BahanBaku::count();
    $menipis = \App\Models\BahanBaku::whereColumn('stok', '<=', 'stok_minimum')->count();

    $ikonBahan = '<path d="M4 8l8-4 8 4v8l-8 4-8-4V8z"/><path d="M4 8l8 4 8-4M12 12v8"/>';
    $ikonOk    = '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>';
    $ikonWarn  = '<path d="M12 4l9 16H3L12 4z"/><path d="M12 10v4M12 17h.01"/>';

    $kartu = [
        ['Total bahan', $total,           $ikonBahan, 'bg-cream text-accent',        'text-neutral-900'],
        ['Stok aman',   $total - $menipis, $ikonOk,   'bg-green-100 text-green-700', 'text-green-700'],
        ['Menipis',     $menipis,         $ikonWarn,  'bg-red-100 text-red-600',     'text-red-600'],
    ];

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="formBahan(@js($daftarBahan), @js($editId ? (int) $editId : null), @js([
         'nama'         => old('nama'),
         'satuan'       => old('satuan'),
         'stok'         => old('stok', 0),
         'stok_minimum' => old('stok_minimum', 0),
     ]), @js($admin))"
     @keydown.escape.window="batal()">

    <div class="grid items-start gap-3 {{ $admin ? 'xl:grid-cols-[minmax(0,1fr)_300px]' : '' }}">

        {{-- ================= KIRI: DAFTAR ================= --}}
        <section class="min-w-0">

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
            <form method="GET" action="{{ route('bahan-baku.index') }}" x-data
                  class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm">
                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari nama bahan..." autocomplete="off"
                           x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                           @input.debounce.500ms="$el.form.submit()"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                <label class="flex h-9 cursor-pointer items-center gap-2 rounded-full border px-4 text-[11px] font-semibold transition
                              {{ request()->boolean('menipis') ? 'border-accent bg-accent text-white' : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50' }}">
                    <input type="checkbox" name="menipis" value="1" class="hidden"
                           @checked(request()->boolean('menipis')) @change="$el.form.submit()">
                    Hanya stok menipis
                </label>
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Nama bahan</th>
                                <th class="px-4 py-2.5 font-semibold">Stok</th>
                                <th class="px-4 py-2.5 font-semibold">Stok minimum</th>
                                <th class="px-4 py-2.5 font-semibold">Status</th>
                                @if ($admin) <th class="px-4 py-2.5 text-right font-semibold">Aksi</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bahan as $item)
                                <tr class="border-t border-neutral-100 transition hover:bg-cream/40"
                                    @if ($admin) :class="id === {{ $item->id }} ? 'bg-cream/60' : ''" @endif>
                                    <td class="px-4 py-2 text-[12px] font-semibold">{{ $item->nama }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 font-semibold {{ $item->menipis() ? 'text-red-600' : '' }}">
                                        {{ $angka($item->stok) }} {{ $item->satuan }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-neutral-500">
                                        {{ $angka($item->stok_minimum) }} {{ $item->satuan }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($item->menipis())
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-semibold text-red-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>Menipis
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-semibold text-green-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Aman
                                            </span>
                                        @endif
                                    </td>
                                    @if ($admin)
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('bahan-baku.edit', $item) }}" @click.prevent="buka({{ $item->id }})"
                                                   title="Ubah bahan" aria-label="Ubah bahan {{ $item->nama }}"
                                                   class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('bahan-baku.destroy', $item) }}"
                                                      onsubmit="return confirm(@js('Hapus bahan ' . $item->nama . '?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus bahan" aria-label="Hapus bahan {{ $item->nama }}"
                                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $admin ? 5 : 4 }}" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonBahan !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada bahan baku yang cocok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $bahan->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: TAMBAH / UBAH (admin) ================= --}}
        @if ($admin)
        <aside class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px]">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path x-show="!id" d="M12 5v14M5 12h14"/>
                        <g x-show="id"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></g>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-[13px] font-semibold leading-tight" x-text="id ? 'Ubah Bahan' : 'Tambah Bahan'"></h2>
                    <p class="truncate text-[10px] text-neutral-500" x-text="id ? m.nama : 'Buat bahan baku baru.'"></p>
                </div>
            </div>

            <form method="POST" :action="id ? m.urlUpdate : @js(route('bahan-baku.store'))"
                  class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                @csrf
                <template x-if="id">
                    <div>
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="_edit_id" :value="id">
                    </div>
                </template>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Nama bahan</label>
                    <input type="text" name="nama" x-model="f.nama" required maxlength="100"
                           placeholder="Contoh: Susu UHT" class="{{ $kelasInput }}">
                    @if ($errors->has('nama'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('nama') }}</p>
                    @endif
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Satuan</label>
                    <input type="text" name="satuan" list="daftar-satuan" x-model="f.satuan" required
                           placeholder="gram, ml, pcs..." class="{{ $kelasInput }}">
                    <datalist id="daftar-satuan">
                        <option value="gram"><option value="kg"><option value="ml"><option value="liter">
                        <option value="pcs"><option value="butir"><option value="sachet"><option value="lembar">
                    </datalist>
                    @if ($errors->has('satuan'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('satuan') }}</p>
                    @endif
                </div>

                {{-- Stok: input saat tambah, tampilan saja saat ubah --}}
                <div>
                    <label class="text-[11px] font-medium text-neutral-700" x-text="id ? 'Stok saat ini' : 'Stok awal'"></label>
                    <template x-if="!id">
                        <input type="number" name="stok" min="0" step="0.01" x-model="f.stok" required class="{{ $kelasInput }}">
                    </template>
                    <template x-if="id">
                        <div>
                            <div class="mt-1 flex h-9 items-center rounded-lg bg-surface px-3 text-[11px] font-semibold text-neutral-700"
                                 x-text="m.stok_teks"></div>
                            <p class="mt-1 text-[10px] leading-snug text-neutral-500">
                                Stok berubah lewat barang masuk, penjualan, atau penyesuaian stok.
                            </p>
                        </div>
                    </template>
                    @if ($errors->has('stok'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('stok') }}</p>
                    @endif
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Stok minimum</label>
                    <input type="number" name="stok_minimum" min="0" step="0.01" x-model="f.stok_minimum" required
                           class="{{ $kelasInput }}">
                    <p class="mt-1 text-[10px] leading-snug text-neutral-500">
                        Status "Menipis" muncul saat stok sama atau di bawah angka ini.
                    </p>
                    @if ($errors->has('stok_minimum'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('stok_minimum') }}</p>
                    @endif
                </div>

                <div class="grid gap-2 pt-1" :class="id ? 'grid-cols-[80px_1fr]' : 'grid-cols-1'">
                    <button type="button" x-show="id" @click="batal()"
                            class="h-9 rounded-full border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                        Batal
                    </button>
                    <button type="submit"
                            class="h-9 rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]"
                            x-text="id ? 'Simpan perubahan' : 'Simpan Bahan'"></button>
                </div>
            </form>
        </aside>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('formBahan', (daftar, bukaId, lama, admin) => ({
        daftar,
        bukaId,
        admin,
        id: null,
        f: { nama: '', satuan: '', stok: 0, stok_minimum: 0 },

        get m() { return this.daftar.find(x => x.id === this.id) || {}; },

        kosong() {
            this.f = { nama: '', satuan: '', stok: 0, stok_minimum: 0 };
        },
        buka(id) {
            const b = this.daftar.find(x => x.id === id);
            if (!b) return;
            this.f = { nama: b.nama, satuan: b.satuan, stok: 0, stok_minimum: b.stok_minimum };
            this.id = id;
        },
        batal() {
            if (this.id === null) return;
            this.id = null;
            this.kosong();
        },

        init() {
            if (!this.admin) return;
            if (this.bukaId) this.buka(this.bukaId);
            // Kalau validasi gagal, pakai isian terakhir pengguna
            for (const k of Object.keys(this.f)) {
                if (lama[k] !== null && lama[k] !== undefined) this.f[k] = lama[k];
            }
        },
    }));
});
</script>
@endpush