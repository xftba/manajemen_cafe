@extends('layouts.admin')

@section('title', 'Peringatan Stok Menipis')

@section('content')
@php
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);

    $ikonBahan = '<path d="M4 8l8-4 8 4v8l-8 4-8-4V8z"/><path d="M4 8l8 4 8-4M12 12v8"/>';
    $ikonWarn  = '<path d="M12 4l9 16H3L12 4z"/><path d="M12 10v4M12 17h.01"/>';
    $ikonHabis = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $kartu = [
        ['Bahan menipis',   $ringkasan['menipis'], $ikonWarn,  'bg-amber-100 text-amber-700', 'text-amber-700'],
        ['Bahan habis',     $ringkasan['habis'],   $ikonHabis, 'bg-red-100 text-red-600',     'text-red-600'],
        ['Total bahan baku', $ringkasan['total'],  $ikonBahan, 'bg-cream text-accent',        'text-neutral-900'],
    ];
@endphp

<div class="mx-auto max-w-[1400px]">

    {{-- Judul dan aksi --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold leading-tight">Peringatan Stok Menipis</h1>
            <p class="text-[11px] text-neutral-500">Diurutkan dari bahan yang paling kritis.</p>
        </div>
        <a href="{{ route('barang-masuk.create') }}"
           class="flex h-9 items-center gap-1.5 rounded-full bg-accent px-4 text-[11px] font-semibold text-white shadow-sm transition hover:opacity-90">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Catat Barang Masuk
        </a>
    </div>

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

    @if ($bahan->isEmpty())
        <div class="rounded-xl bg-green-50 px-4 py-12 text-center shadow-sm">
            <svg class="mx-auto h-9 w-9 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
            <p class="mt-2 text-[13px] font-semibold text-green-800">Semua stok aman</p>
            <p class="mt-0.5 text-[11px] text-green-700">Belum ada bahan yang mencapai batas minimum.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Bahan</th>
                            <th class="px-4 py-2.5 font-semibold">Stok saat ini</th>
                            <th class="px-4 py-2.5 font-semibold">Stok minimum</th>
                            <th class="px-4 py-2.5 font-semibold">Kekurangan</th>
                            <th class="px-4 py-2.5 font-semibold">Dipakai di</th>
                            <th class="px-4 py-2.5 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bahan as $b)
                            @php
                                $habis   = (float) $b->stok <= 0;
                                $minimum = (float) $b->stok_minimum;
                                $persen  = $minimum > 0 ? (int) min(100, round((float) $b->stok / $minimum * 100)) : 0;
                            @endphp
                            <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                                <td class="px-4 py-2 text-[12px] font-semibold">{{ $b->nama }}</td>

                                <td class="px-4 py-2">
                                    <p class="whitespace-nowrap font-semibold {{ $habis ? 'text-red-600' : '' }}">{{ $angka($b->stok) }} {{ $b->satuan }}</p>
                                    <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-neutral-200">
                                        <div class="h-full rounded-full {{ $habis ? 'bg-red-600' : 'bg-amber-500' }}" style="width: {{ $persen }}%"></div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ $angka($b->stok_minimum) }} {{ $b->satuan }}</td>

                                <td class="whitespace-nowrap px-4 py-2 font-semibold text-red-600">
                                    @if ($minimum > (float) $b->stok)
                                        {{ $angka($b->stok_minimum - $b->stok) }} {{ $b->satuan }}
                                    @else
                                        <span class="font-normal text-neutral-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2">
                                    <span class="inline-block rounded-full bg-cream px-2.5 py-0.5 text-[10px] font-medium text-accent">
                                        {{ $b->resep_count }} menu
                                    </span>
                                </td>

                                <td class="px-4 py-2">
                                    @if ($habis)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-3 text-[10px] text-neutral-500">
            Kekurangan adalah selisih stok saat ini dengan batas minimum. Bar menunjukkan stok dibanding batas minimum.
        </p>
    @endif
</div>
@endsection