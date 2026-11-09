@extends('layouts.admin')

@section('title', 'Kategori Menu')

@section('content')
@php
    $editId = old('_edit_id');

    $daftarKategori = $kategori->getCollection()->map(fn ($k) => [
        'id'        => $k->id,
        'nama'      => $k->nama,
        'urlUpdate' => route('kategori.update', $k),
    ])->values();

    // Supaya galat dan isian form Ubah tidak muncul di form Tambah
    $lama  = fn ($k) => $editId ? null : old($k);
    $galat = fn ($k) => $editId ? null : $errors->first($k);

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
    $kelasPopup = 'mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent';

    $ikon = '<path d="M6 9h10v4a4 4 0 01-4 4h-2a4 4 0 01-4-4V9z"/><path d="M16 10h1a2 2 0 010 4h-1"/><path d="M9 4v2M13 4v2"/>';
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="editKategori(@js($daftarKategori), @js($editId ? (int) $editId : null), @js($editId ? old('nama') : null))"
     @keydown.escape.window="tutup()">

    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_300px]">

        {{-- ================= KIRI: DAFTAR KATEGORI ================= --}}
        <section class="min-w-0">

            {{-- Ringkasan --}}
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cream text-accent">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                </span>
                <div>
                    <p class="text-[11px] text-neutral-500">Total kategori</p>
                    <p class="text-xl font-semibold leading-tight">{{ $kategori->total() }}</p>
                </div>
            </div>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="w-14 px-4 py-2.5 font-semibold">No</th>
                                <th class="px-4 py-2.5 font-semibold">Nama</th>
                                <th class="px-4 py-2.5 font-semibold">Jumlah menu</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kategori as $item)
                                <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                                    <td class="px-4 py-2.5 text-neutral-500">{{ $kategori->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-2.5 text-[12px] font-semibold">{{ $item->nama }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-block rounded-full bg-cream px-2.5 py-0.5 text-[10px] font-medium text-accent">
                                            {{ $item->menu_count }} menu
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('kategori.edit', $item) }}" @click.prevent="buka({{ $item->id }})"
                                               title="Ubah kategori" aria-label="Ubah kategori {{ $item->nama }}"
                                               class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                                </svg>
                                            </a>

                                            <form method="POST" action="{{ route('kategori.destroy', $item) }}"
                                                  onsubmit="return confirm(@js('Hapus kategori ' . $item->nama . '?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus kategori" aria-label="Hapus kategori {{ $item->nama }}"
                                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada kategori.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $kategori->links() }}</div>
        </section>

        {{-- ================= KANAN: FORM TAMBAH KATEGORI ================= --}}
        <aside class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px]">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </span>
                <h2 class="text-[13px] font-semibold leading-tight">Tambah Kategori</h2>
            </div>

            <form method="POST" action="{{ route('kategori.store') }}"
                  class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                @csrf

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Nama kategori</label>
                    <input type="text" name="nama" value="{{ $lama('nama') }}" required maxlength="100"
                           placeholder="Contoh: Coffee" class="{{ $kelasInput }}">
                    @if ($galat('nama')) <p class="mt-1 text-[10px] text-red-600">{{ $galat('nama') }}</p> @endif
                </div>

                <button type="submit"
                        class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                    Simpan Kategori
                </button>
            </form>
        </aside>
    </div>

    {{-- ================= POPUP UBAH KATEGORI ================= --}}
    <div x-show="id !== null" x-cloak @click.self="tutup()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

        <template x-if="k">
            <div class="w-full max-w-[340px] rounded-2xl bg-white p-4 shadow-xl">

                <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                    </span>
                    <h3 class="text-[13px] font-semibold">Ubah Kategori</h3>
                </div>

                <form method="POST" :action="k.urlUpdate">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_edit_id" :value="k.id">

                    <label class="mt-3 block text-[11px] font-semibold">Nama kategori</label>
                    <input type="text" name="nama" x-model="nama" required maxlength="100"
                           x-ref="inputNama" class="{{ $kelasPopup }}">
                    @if ($errors->has('nama') && $editId)
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('nama') }}</p>
                    @endif

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
    Alpine.data('editKategori', (daftar, bukaId, namaLama) => ({
        daftar,
        bukaId,
        id: null,
        nama: '',

        get k() { return this.daftar.find(x => x.id === this.id) || null; },

        buka(id) {
            const k = this.daftar.find(x => x.id === id);
            if (!k) return;
            this.nama = k.nama;
            this.id = id;
            this.$nextTick(() => this.$refs.inputNama?.focus());
        },
        tutup() { this.id = null; },

        init() {
            if (!this.bukaId) return;
            this.buka(this.bukaId);
            // Kalau validasi gagal, pakai isian terakhir pengguna
            if (namaLama !== null && namaLama !== undefined) this.nama = namaLama;
        },
    }));
});
</script>
@endpush