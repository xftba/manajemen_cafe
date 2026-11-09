@extends('layouts.admin')

@section('title', 'Laporan Persediaan')

@section('content')
@php
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);
    $rp    = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $rute  = auth()->user()->role === 'admin' ? 'laporan.persediaan' : 'laporan.bahan-baku';

    $adaFilter = filled($cari) || filled($status);

    $ikonStok  = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>';
    $ikonWarn  = '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>';
    $ikonHabis = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $kartu = [
        ['Total bahan baku', $ringkasan['total'],   $ikonStok,  'bg-cream text-accent',      'text-neutral-900'],
        ['Stok menipis',     $ringkasan['menipis'], $ikonWarn,  'bg-amber-100 text-amber-700', 'text-amber-700'],
        ['Stok habis',       $ringkasan['habis'],   $ikonHabis, 'bg-red-100 text-red-600',   'text-red-600'],
    ];
@endphp

<div class="mx-auto max-w-[1400px] print:max-w-none">

    @include('laporan._kepala', [
        'judul'   => 'Laporan Persediaan Bahan Baku',
        'periode' => 'Per tanggal ' . now()->translatedFormat('d F Y H:i'),
    ])

    @include('laporan._kartu', ['kartu' => $kartu, 'kolom' => 'grid-cols-1 sm:grid-cols-3'])

    {{-- Pencarian dan filter --}}
    <form method="GET" action="{{ route($rute) }}" x-data
          class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm print:hidden">

        <div class="relative min-w-[200px] flex-1">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
            </svg>
            <input type="text" name="q" value="{{ $cari }}" placeholder="Cari nama bahan..." autocomplete="off"
                   x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                   @input.debounce.500ms="$el.form.submit()"
                   class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <x-pilihan name="status"
                   :options="['menipis' => 'Menipis (termasuk habis)', 'habis' => 'Habis']"
                   :selected="$status"
                   semua="Semua status" />

        @if ($adaFilter)
            <a href="{{ route($rute) }}"
               class="flex h-9 items-center rounded-full border border-neutral-300 px-4 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50">
                Reset
            </a>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Bahan</th>
                        <th class="px-4 py-2.5 font-semibold">Stok</th>
                        <th class="px-4 py-2.5 font-semibold">Stok minimum</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Harga beli terakhir</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Perkiraan nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bahan as $r)
                        @php $b = $r['bahan']; @endphp
                        <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                            <td class="px-4 py-2 text-[12px] font-semibold">{{ $b->nama }}</td>
                            <td class="whitespace-nowrap px-4 py-2 font-semibold">{{ $angka($b->stok) }} <span class="font-normal text-neutral-400">{{ $b->satuan }}</span></td>
                            <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ $angka($b->stok_minimum) }} {{ $b->satuan }}</td>
                            <td class="px-4 py-2">
                                @if ((float) $b->stok <= 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-semibold text-red-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>Habis
                                    </span>
                                @elseif ($b->menipis())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-semibold text-green-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Aman
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-right {{ $r['harga'] > 0 ? '' : 'text-neutral-300' }}">
                                {{ $r['harga'] > 0 ? $rp($r['harga']) . ' / ' . $b->satuan : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-right font-semibold {{ $r['harga'] > 0 ? '' : 'font-normal text-neutral-300' }}">
                                {{ $r['harga'] > 0 ? $rp($r['nilai']) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonStok !!}</svg>
                                <p class="mt-2 text-xs text-neutral-400">Tidak ada bahan baku yang cocok.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($bahan->isNotEmpty())
                    <tfoot>
                        <tr class="border-t border-neutral-300 bg-cream/50 font-semibold">
                            <td colspan="5" class="px-4 py-2.5 text-right">Total perkiraan nilai</td>
                            <td class="px-4 py-2.5 text-right">{{ $rp($bahan->sum('nilai')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection