@extends('layouts.admin')

@section('title', 'Data Supplier')

@section('content')
@php
    $admin  = auth()->user()->role === 'admin';
    $editId = old('_edit_id');

    $daftarSupplier = $supplier->getCollection()->map(fn ($s) => [
        'id'        => $s->id,
        'nama'      => $s->nama,
        'telepon'   => $s->telepon,
        'alamat'    => $s->alamat,
        'urlUpdate' => route('supplier.update', $s),
    ])->values();

    $total = \App\Models\Supplier::count();

    $ikonSupplier = '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>';

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="formSupplier(@js($daftarSupplier), @js($editId ? (int) $editId : null), @js([
         'nama'    => old('nama'),
         'telepon' => old('telepon'),
         'alamat'  => old('alamat'),
     ]), @js($admin))"
     @keydown.escape.window="batal()">

    <div class="grid items-start gap-3 {{ $admin ? 'xl:grid-cols-[minmax(0,1fr)_300px]' : '' }}">

        {{-- ================= KIRI: DAFTAR ================= --}}
        <section class="min-w-0">

            {{-- Ringkasan --}}
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cream text-accent">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonSupplier !!}</svg>
                </span>
                <div>
                    <p class="text-[11px] text-neutral-500">Total supplier</p>
                    <p class="text-xl font-semibold leading-tight">{{ $total }}</p>
                </div>
            </div>

            {{-- Pencarian --}}
            <form method="GET" action="{{ route('supplier.index') }}" x-data
                  class="mb-3 rounded-xl bg-white p-3 shadow-sm">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari nama supplier..." autocomplete="off"
                           x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                           @input.debounce.500ms="$el.form.submit()"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Nama supplier</th>
                                <th class="px-4 py-2.5 font-semibold">Telepon</th>
                                <th class="px-4 py-2.5 font-semibold">Alamat</th>
                                @if ($admin) <th class="px-4 py-2.5 text-right font-semibold">Aksi</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($supplier as $item)
                                <tr class="border-t border-neutral-100 transition hover:bg-cream/40"
                                    @if ($admin) :class="id === {{ $item->id }} ? 'bg-cream/60' : ''" @endif>
                                    <td class="px-4 py-2 text-[12px] font-semibold">{{ $item->nama }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $item->telepon ?: '-' }}</td>
                                    <td class="max-w-[260px] px-4 py-2 text-neutral-600">
                                        <p class="truncate" title="{{ $item->alamat }}">{{ $item->alamat ?: '-' }}</p>
                                    </td>
                                    @if ($admin)
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('supplier.edit', $item) }}" @click.prevent="buka({{ $item->id }})"
                                                   title="Ubah supplier" aria-label="Ubah supplier {{ $item->nama }}"
                                                   class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('supplier.destroy', $item) }}"
                                                      onsubmit="return confirm(@js('Hapus supplier ' . $item->nama . '?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus supplier" aria-label="Hapus supplier {{ $item->nama }}"
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
                                    <td colspan="{{ $admin ? 4 : 3 }}" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonSupplier !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada supplier yang cocok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $supplier->links() }}</div>
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
                    <h2 class="text-[13px] font-semibold leading-tight" x-text="id ? 'Ubah Supplier' : 'Tambah Supplier'"></h2>
                    <p class="truncate text-[10px] text-neutral-500" x-text="id ? m.nama : 'Buat supplier baru.'"></p>
                </div>
            </div>

            <form method="POST" :action="id ? m.urlUpdate : @js(route('supplier.store'))"
                  class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                @csrf
                <template x-if="id">
                    <div>
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="_edit_id" :value="id">
                    </div>
                </template>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Nama supplier</label>
                    <input type="text" name="nama" x-model="f.nama" required maxlength="100"
                           placeholder="Contoh: CV Sumber Rasa" class="{{ $kelasInput }}">
                    @if ($errors->has('nama'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('nama') }}</p>
                    @endif
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Telepon</label>
                    <input type="text" name="telepon" x-model="f.telepon" maxlength="30"
                           placeholder="08xxxxxxxxxx" class="{{ $kelasInput }}">
                    @if ($errors->has('telepon'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('telepon') }}</p>
                    @endif
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Alamat</label>
                    <textarea name="alamat" x-model="f.alamat" rows="3" placeholder="Alamat supplier"
                              class="mt-1 w-full resize-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                    @if ($errors->has('alamat'))
                        <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('alamat') }}</p>
                    @endif
                </div>

                <div class="grid gap-2 pt-1" :class="id ? 'grid-cols-[80px_1fr]' : 'grid-cols-1'">
                    <button type="button" x-show="id" @click="batal()"
                            class="h-9 rounded-full border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                        Batal
                    </button>
                    <button type="submit"
                            class="h-9 rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]"
                            x-text="id ? 'Simpan perubahan' : 'Simpan Supplier'"></button>
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
    Alpine.data('formSupplier', (daftar, bukaId, lama, admin) => ({
        daftar,
        bukaId,
        admin,
        id: null,
        f: { nama: '', telepon: '', alamat: '' },

        get m() { return this.daftar.find(x => x.id === this.id) || {}; },

        kosong() { this.f = { nama: '', telepon: '', alamat: '' }; },
        buka(id) {
            const s = this.daftar.find(x => x.id === id);
            if (!s) return;
            this.f = { nama: s.nama, telepon: s.telepon || '', alamat: s.alamat || '' };
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