@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $rp         = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $angka      = fn ($n) => \App\Models\BahanBaku::angka($n);
    $namaMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS'];

    $labelHarian   = $harian->map(fn ($h) => $h['tanggal']->translatedFormat('d M'))->values()->all();
    $dataHarian    = $harian->map(fn ($h) => $h['omset'])->values()->all();
    $labelKategori = $kategori->pluck('nama')->values()->all();
    $dataKategori  = $kategori->map(fn ($k) => (float) $k->omset)->values()->all();
    $labelMetode   = $metode->map(fn ($m) => $namaMetode[$m->metode] ?? $m->metode)->values()->all();
    $dataMetode    = $metode->map(fn ($m) => (float) $m->omset)->values()->all();

    // Ikon kartu statistik (isi SVG saja)
    $ikonHari   = '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>';
    $ikonBulan  = '<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>';
    $ikonRata   = '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>';
    $ikonStok   = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>';
@endphp

<div class="mx-auto max-w-[1400px]">

    {{-- Notifikasi stok menipis --}}
    @if ($persediaan['menipis'] > 0)
        <a href="{{ route('bahan-baku.index', ['menipis' => 1]) }}"
           class="mb-4 flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-2 text-[11px] text-amber-900 ring-1 ring-amber-200 transition hover:bg-amber-100">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                </svg>
                <span>
                    <strong>{{ $persediaan['menipis'] }} bahan baku</strong> sudah mencapai batas stok minimum.
                    @if ($persediaan['habis'] > 0)
                        {{ $persediaan['habis'] }} di antaranya habis.
                    @endif
                </span>
            </span>
            <span class="shrink-0 font-semibold underline">Lihat</span>
        </a>
    @endif

    {{-- Statistik --}}
    <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[11px] text-neutral-500">Penjualan hari ini</p>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">{!! $ikonHari !!}</svg>
                </span>
            </div>
            <p class="mt-1.5 text-lg font-semibold leading-tight">{{ $rp($hariIni['omset']) }}</p>
            <p class="mt-0.5 text-[10px] text-neutral-400">{{ $hariIni['jumlah'] }} transaksi</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[11px] text-neutral-500">Penjualan bulan ini</p>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">{!! $ikonBulan !!}</svg>
                </span>
            </div>
            <p class="mt-1.5 text-lg font-semibold leading-tight">{{ $rp($bulanIni['omset']) }}</p>
            <p class="mt-0.5 text-[10px] text-neutral-400">{{ $bulanIni['jumlah'] }} transaksi, {{ $bulanIni['batal'] }} dibatalkan</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[11px] text-neutral-500">Rata-rata per transaksi</p>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">{!! $ikonRata !!}</svg>
                </span>
            </div>
            <p class="mt-1.5 text-lg font-semibold leading-tight">{{ $rp($bulanIni['rata']) }}</p>
            <p class="mt-0.5 text-[10px] text-neutral-400">Pajak bulan ini {{ $rp($bulanIni['pajak']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[11px] text-neutral-500">Persediaan bahan baku</p>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">{!! $ikonStok !!}</svg>
                </span>
            </div>
            <p class="mt-1.5 text-lg font-semibold leading-tight {{ $persediaan['menipis'] ? 'text-amber-700' : 'text-green-700' }}">
                {{ $persediaan['menipis'] }} menipis
            </p>
            <p class="mt-0.5 text-[10px] text-neutral-400">dari {{ $persediaan['total'] }} bahan, {{ $persediaan['habis'] }} habis</p>
        </div>
    </div>

    {{-- Grafik utama --}}
    <div class="mb-4 grid gap-3 xl:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow-sm xl:col-span-2">
            <p class="mb-2 text-[13px] font-semibold">Penjualan 7 hari terakhir</p>
            <div class="h-52"><canvas id="grafik-harian"></canvas></div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="mb-2 text-[13px] font-semibold">Metode pembayaran <span class="font-normal text-neutral-400">(bulan ini)</span></p>
            @if ($metode->isEmpty())
                <p class="py-14 text-center text-[11px] text-neutral-400">Belum ada transaksi bulan ini.</p>
            @else
                <div class="h-52"><canvas id="grafik-metode"></canvas></div>
            @endif
        </div>
    </div>

    {{-- Kategori dan menu terlaris --}}
    <div class="mb-4 grid gap-3 xl:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="mb-2 text-[13px] font-semibold">Penjualan per kategori <span class="font-normal text-neutral-400">(bulan ini)</span></p>
            @if ($kategori->isEmpty())
                <p class="py-14 text-center text-[11px] text-neutral-400">Belum ada penjualan bulan ini.</p>
            @else
                <div class="h-52"><canvas id="grafik-kategori"></canvas></div>
            @endif
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="mb-2 text-[13px] font-semibold">Menu terlaris <span class="font-normal text-neutral-400">(bulan ini)</span></p>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="rounded-l-lg px-3 py-2 font-semibold">Menu</th>
                            <th class="px-3 py-2 text-center font-semibold">Terjual</th>
                            <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($terlaris as $i => $m)
                            <tr class="border-t border-neutral-100">
                                <td class="px-3 py-1.5 font-medium">
                                    <span class="mr-1.5 text-neutral-400">{{ $i + 1 }}.</span>{{ $m->nama }}
                                </td>
                                <td class="px-3 py-1.5 text-center">{{ (int) $m->terjual }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $rp($m->omset) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-8 text-center text-neutral-400">Belum ada penjualan bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Transaksi terbaru dan stok menipis --}}
    <div class="grid gap-3 xl:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-[13px] font-semibold">Transaksi terbaru</p>
                <a href="{{ route('transaksi.index') }}" class="text-[11px] font-medium text-accent hover:underline">Lihat semua</a>
            </div>
            <table class="w-full text-[11px]">
                <tbody>
                    @forelse ($terbaru as $t)
                        <tr class="border-t border-neutral-100 first:border-0">
                            <td class="py-2">
                                <a href="{{ route('transaksi.show', $t) }}" class="font-medium text-accent hover:underline">{{ $t->no_invoice }}</a>
                                <p class="text-[10px] text-neutral-500">{{ $t->tanggal_transaksi->format('d/m/Y H:i') }} • {{ $t->user->nama }}</p>
                            </td>
                            <td class="py-2 text-right">
                                <p class="{{ $t->status === 'batal' ? 'text-neutral-400 line-through' : 'font-semibold' }}">{{ $rp($t->total) }}</p>
                                <span class="inline-block rounded-full px-2 py-px text-[9px] font-semibold {{ $t->status === 'batal' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $t->status === 'batal' ? 'Batal' : 'Selesai' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-8 text-center text-neutral-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-[13px] font-semibold">Stok menipis</p>
                <a href="{{ route('bahan-baku.index', ['menipis' => 1]) }}" class="text-[11px] font-medium text-accent hover:underline">Lihat semua</a>
            </div>
            <table class="w-full text-[11px]">
                <tbody>
                    @forelse ($menipis as $b)
                        <tr class="border-t border-neutral-100 first:border-0">
                            <td class="py-2">
                                <p class="font-medium">{{ $b->nama }}</p>
                                <p class="text-[10px] text-neutral-500">Minimum {{ $angka($b->stok_minimum) }} {{ $b->satuan }}</p>
                            </td>
                            <td class="py-2 text-right">
                                <p class="font-semibold">{{ $angka($b->stok) }} {{ $b->satuan }}</p>
                                <span class="inline-block rounded-full px-2 py-px text-[9px] font-semibold {{ (float) $b->stok <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ (float) $b->stok <= 0 ? 'Habis' : 'Menipis' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-8 text-center text-green-700">Semua stok aman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    grafikGaris('grafik-harian', @js($labelHarian), @js($dataHarian));
    grafikDonat('grafik-metode', @js($labelMetode), @js($dataMetode));
    grafikBatang('grafik-kategori', @js($labelKategori), @js($dataKategori), true, true);
});
</script>
@endpush