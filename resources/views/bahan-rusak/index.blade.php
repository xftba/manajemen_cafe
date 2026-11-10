@extends('layouts.admin')

@section('title', 'Bahan Rusak / Kadaluarsa')

@section('content')
@php
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);

    $adaFilter = request('jenis') || request('dari') || request('sampai');
    $ikonRusak = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
@endphp

<div class="mx-auto max-w-[1400px]">
    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_320px]">

        {{-- ================= KIRI: RIWAYAT ================= --}}
        <section class="min-w-0">

            {{-- Filter --}}
            <form method="GET" action="{{ route('bahan-rusak.index') }}" x-data
                  class="mb-3 flex flex-wrap items-end gap-3 rounded-xl bg-white p-3 shadow-sm">

                <div class="w-[170px]">
                    <label class="text-[10px] font-medium text-neutral-500">Jenis</label>
                    <x-pilihan name="jenis"
                               :options="['rusak' => 'Rusak', 'kadaluarsa' => 'Kadaluarsa']"
                               :selected="request('jenis')"
                               semua="Semua jenis" />
                </div>

                <div class="w-[150px]">
                    <label class="text-[10px] font-medium text-neutral-500">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ request('dari') }}" @change="$el.form.submit()"
                           class="mt-1.5 h-9 w-full rounded-full border-0 bg-surface px-4 text-[11px] text-neutral-700 focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                <div class="w-[150px]">
                    <label class="text-[10px] font-medium text-neutral-500">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" @change="$el.form.submit()"
                           class="mt-1.5 h-9 w-full rounded-full border-0 bg-surface px-4 text-[11px] text-neutral-700 focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                @if ($adaFilter)
                    <a href="{{ route('bahan-rusak.index') }}"
                       class="flex h-9 items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Waktu</th>
                                <th class="px-4 py-2.5 font-semibold">Bahan</th>
                                <th class="px-4 py-2.5 font-semibold">Jenis</th>
                                <th class="px-4 py-2.5 font-semibold">Jumlah</th>
                                <th class="px-4 py-2.5 font-semibold">Stok</th>
                                <th class="px-4 py-2.5 font-semibold">Keterangan</th>
                                <th class="px-4 py-2.5 font-semibold">Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayat as $r)
                                <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">
                                    <td class="whitespace-nowrap px-4 py-2">
                                        <p class="font-semibold">{{ $r->created_at->format('d/m/Y') }}</p>
                                        <p class="text-[10px] text-neutral-400">{{ $r->created_at->format('H:i') }}</p>
                                    </td>
                                    <td class="px-4 py-2 text-[12px] font-semibold">{{ $r->bahanBaku->nama }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>{{ $r->label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 font-semibold text-red-600">
                                        −{{ $angka($r->jumlah) }} {{ $r->bahanBaku->satuan }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-neutral-500">
                                        {{ $angka($r->stok_sebelum) }} <span class="text-neutral-300">→</span>
                                        <span class="font-semibold text-neutral-800">{{ $angka($r->stok_sesudah) }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <p class="max-w-[220px] truncate text-neutral-600" title="{{ $r->catatan }}">{{ $r->catatan ?: '-' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $r->user->nama }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonRusak !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada catatan yang cocok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $riwayat->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: CATAT ================= --}}
        <aside class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px]">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-red-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonRusak !!}</svg>
                </span>
                <div>
                    <h2 class="text-[13px] font-semibold leading-tight">Catat Rusak / Kadaluarsa</h2>
                    <p class="text-[10px] text-neutral-500">Stok langsung berkurang.</p>
                </div>
            </div>

            @if ($bahan->isEmpty())
                <div class="mt-3 rounded-xl bg-amber-50 px-3 py-3 text-[11px] text-amber-900 ring-1 ring-amber-200">
                    Belum ada bahan baku. Minta admin menambahkannya di Data Bahan Baku.
                </div>
            @else
                <form method="POST" action="{{ route('bahan-rusak.store') }}"
                      x-data="rusakForm(@js($bahan), @js(old('bahan_baku_id', '')), @js(old('jenis', 'rusak')), @js(old('jumlah', '')))"
                      @submit="kirim = true"
                      class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-lg bg-red-100 px-3 py-2 text-[10px] text-red-800">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $pesan) <li>{{ $pesan }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Bahan (dropdown Alpine.js) --}}
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Bahan baku</label>
                        <div class="relative" @click.outside="buka = false">
                            <input type="hidden" name="bahan_baku_id" :value="bahanId">
                            <button type="button" @click="buka = !buka"
                                    :class="buka ? 'ring-2 ring-accent' : ''"
                                    class="mt-1 flex h-9 w-full items-center justify-between gap-1 rounded-lg bg-surface px-3 text-left text-[11px] transition">
                                <span class="truncate" :class="terpilih ? '' : 'text-neutral-400'"
                                      x-text="terpilih ? terpilih.nama : 'Pilih bahan'"></span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-neutral-500 transition-transform" :class="buka ? 'rotate-180' : ''"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                            </button>

                            <div x-show="buka" x-cloak x-transition.origin.top
                                 class="absolute left-0 top-full z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">
                                <template x-for="b in bahan" :key="b.id">
                                    <button type="button" @click="bahanId = String(b.id); buka = false"
                                            :class="String(b.id) === bahanId ? 'bg-cream font-semibold text-accent' : 'text-neutral-700 hover:bg-cream/60'"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-[11px] transition">
                                        <span class="truncate" x-text="b.nama"></span>
                                        <span class="shrink-0 text-[10px] font-normal text-neutral-400" x-text="angka(b.stok) + ' ' + b.satuan"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1 text-[10px] text-neutral-500" x-show="terpilih"
                           x-text="'Stok saat ini: ' + angka(terpilih.stok) + ' ' + terpilih.satuan"></p>
                    </div>

                    {{-- Jenis --}}
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Jenis</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <label class="flex h-9 cursor-pointer items-center justify-center rounded-full border text-[11px] font-semibold transition"
                                   :class="jenis === 'rusak' ? 'border-accent bg-accent text-white' : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50'">
                                <input type="radio" name="jenis" value="rusak" x-model="jenis" class="sr-only"> Rusak
                            </label>
                            <label class="flex h-9 cursor-pointer items-center justify-center rounded-full border text-[11px] font-semibold transition"
                                   :class="jenis === 'kadaluarsa' ? 'border-accent bg-accent text-white' : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50'">
                                <input type="radio" name="jenis" value="kadaluarsa" x-model="jenis" class="sr-only"> Kadaluarsa
                            </label>
                        </div>
                    </div>

                    {{-- Jumlah --}}
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">
                            Jumlah <span class="font-normal text-neutral-400" x-text="terpilih ? '(' + terpilih.satuan + ')' : ''"></span>
                        </label>
                        <input type="number" name="jumlah" min="0" step="0.01" x-model="jumlah" required
                               placeholder="0" class="{{ $kelasInput }}">
                        <p class="mt-1 text-[10px] text-red-600" x-show="melebihi">Jumlah melebihi stok yang tersedia.</p>
                    </div>

                    {{-- Keterangan --}}
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Keterangan</label>
                        <input type="text" name="keterangan" value="{{ old('keterangan') }}" maxlength="255" required
                               placeholder="Contoh: susu basi, kemasan bocor" class="{{ $kelasInput }}">
                    </div>

                    <button type="submit" :disabled="kirim || melebihi || !bahanId"
                            class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50">
                        Simpan
                    </button>
                </form>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rusakForm', (bahan, bahanAwal, jenisAwal, jumlahAwal) => ({
        bahan,
        bahanId: String(bahanAwal || ''),
        jenis: jenisAwal,
        jumlah: jumlahAwal,
        buka: false,    // dropdown bahan
        kirim: false,   // mencegah tombol simpan ditekan dua kali

        get terpilih() { return this.bahan.find(b => String(b.id) === String(this.bahanId)) || null; },
        get melebihi() { return !!this.terpilih && parseFloat(this.jumlah) > this.terpilih.stok; },
        angka(n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
    }));
});
</script>
@endpush