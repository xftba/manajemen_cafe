@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);

    $warna = [
        'stok_awal'   => 'bg-neutral-100 text-neutral-700',
        'masuk'       => 'bg-green-100 text-green-800',
        'keluar'      => 'bg-blue-100 text-blue-800',
        'pembatalan'  => 'bg-amber-100 text-amber-800',
        'rusak'       => 'bg-red-100 text-red-700',
        'kadaluarsa'  => 'bg-red-100 text-red-700',
        'penyesuaian' => 'bg-purple-100 text-purple-800',
    ];

    $ikonBahan = '<path d="M4 8l8-4 8 4v8l-8 4-8-4V8z"/><path d="M4 8l8 4 8-4M12 12v8"/>';
    $ikonWarn  = '<path d="M12 4l9 16H3L12 4z"/><path d="M12 10v4M12 17h.01"/>';
    $ikonMasuk = '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M5 20h14"/>';
    $ikonRusak = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $kartu = [
        ['Total bahan baku',   $persediaan['total'],   $persediaan['habis'] . ' di antaranya habis' , null, $ikonBahan, 'bg-cream text-accent',         'text-neutral-900'],
        ['Stok menipis',       $persediaan['menipis'], $persediaan['habis'] . ' di antaranya habis', null, $ikonWarn,  'bg-amber-100 text-amber-700',  'text-amber-700'],
        ['Barang masuk',       $barangMasuk,           'penerimaan bulan ini',                       null, $ikonMasuk, 'bg-green-100 text-green-700',  'text-green-700'],
        ['Rusak / kadaluarsa', $rusak,                 'pencatatan bulan ini',                       null, $ikonRusak, 'bg-red-100 text-red-600',      'text-red-600'],
    ];
    $kartu[0][2] = 'jenis bahan terdaftar';
@endphp

<div class="mx-auto max-w-[1400px]">

    {{-- Sapaan --}}
    <div class="mb-3 flex flex-wrap items-end justify-left gap-2">

        {{-- Pintasan --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('barang-masuk.create') }}"
               class="flex h-9 items-center gap-1.5 rounded-full bg-accent px-4 text-[11px] font-semibold text-white shadow-sm transition hover:opacity-90">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Catat Barang Masuk
            </a>
            <a href="{{ route('bahan-rusak.index') }}"
               class="flex h-9 items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                Catat Bahan Rusak
            </a>
            <a href="{{ route('penyesuaian-stok.create') }}"
               class="flex h-9 items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                Ajukan Penyesuaian
            </a>
            @if ($menunggu > 0)
                <a href="{{ route('penyesuaian-stok.index', ['status' => 'pending']) }}"
                   class="flex h-9 items-center gap-1.5 rounded-full bg-amber-100 px-4 text-[11px] font-semibold text-amber-900 transition hover:bg-amber-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                    {{ $menunggu }} pengajuan menunggu admin
                </a>
            @endif
        </div>
    </div>

    {{-- Peringatan --}}
    @if ($persediaan['menipis'] > 0)
        <a href="{{ route('stok-menipis.index') }}"
           class="mb-3 flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-2.5 text-[11px] text-amber-900 ring-1 ring-amber-200 transition hover:bg-amber-100/60">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonWarn !!}</svg>
                <span>
                    <strong>{{ $persediaan['menipis'] }} bahan baku</strong> sudah mencapai batas stok minimum.
                    @if ($persediaan['habis'] > 0) {{ $persediaan['habis'] }} di antaranya habis. @endif
                </span>
            </span>
            <span class="shrink-0 font-semibold underline">Lihat peringatan</span>
        </a>
    @endif

    {{-- Ringkasan --}}
    <div class="mb-3 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ($kartu as [$judul, $nilai, $keterangan, , $ikon, $warnaIkon, $warnaAngka])
            <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[11px] text-neutral-500">{{ $judul }}</p>
                    <p class="text-xl font-semibold leading-tight {{ $warnaAngka }}">{{ $nilai }}</p>
                    <p class="truncate text-[10px] text-neutral-400">{{ $keterangan }}</p>
                </div>
            </div>
        @endforeach
    </div>


    <div class="grid items-start gap-3 xl:grid-cols-2">

        {{-- Stok menipis --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">
                <p class="text-[13px] font-semibold">Peringatan stok menipis</p>
                <a href="{{ route('stok-menipis.index') }}" class="text-[11px] font-semibold text-accent hover:underline">Lihat semua</a>
            </div>
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2 font-semibold">Bahan</th>
                        <th class="px-4 py-2 font-semibold">Stok</th>
                        <th class="px-4 py-2 font-semibold">Minimum</th>
                        <th class="px-4 py-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menipis as $b)
                        <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                            <td class="px-4 py-2 text-[12px] font-semibold">{{ $b->nama }}</td>
                            <td class="whitespace-nowrap px-4 py-2 font-semibold {{ (float) $b->stok <= 0 ? 'text-red-600' : '' }}">{{ $angka($b->stok) }} {{ $b->satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ $angka($b->stok_minimum) }} {{ $b->satuan }}</td>
                            <td class="px-4 py-2">
                                @if ((float) $b->stok <= 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-semibold text-red-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>Habis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>Menipis
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center">
                                <svg class="mx-auto h-8 w-8 text-green-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
                                <p class="mt-2 text-xs text-green-700">Semua stok aman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pergerakan terbaru --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">
                <p class="text-[13px] font-semibold">Pergerakan stok terbaru</p>
                <a href="{{ route('pergerakan-stok.index') }}" class="text-[11px] font-semibold text-accent hover:underline">Lihat semua</a>
            </div>
            <div>
                @forelse ($pergerakan as $p)
                    @php $selisih = $p->selisih; @endphp
                    <div class="flex items-center justify-between gap-3 border-t border-neutral-100 px-4 py-2 transition hover:bg-cream/40">
                        <div class="min-w-0">
                            <p class="truncate text-[12px] font-semibold">{{ $p->bahanBaku->nama }}</p>
                            <p class="truncate text-[10px] text-neutral-400">
                                {{ $p->created_at->format('d/m/Y H:i') }} • {{ $p->user->nama }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] font-semibold {{ $selisih >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ $selisih >= 0 ? '+' : '−' }}{{ $angka(abs($selisih)) }} {{ $p->bahanBaku->satuan }}
                            </p>
                            <span class="mt-0.5 inline-block rounded-full px-2 py-px text-[9px] font-semibold {{ $warna[$p->tipe_pergerakan] ?? 'bg-neutral-100 text-neutral-700' }}">{{ $p->label }}</span>
                        </div>
                    </div>
                @empty
                    <div class="border-t border-neutral-100 px-4 py-10 text-center">
                        <p class="text-xs text-neutral-400">Belum ada pergerakan stok.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection