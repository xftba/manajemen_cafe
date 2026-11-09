@extends('layouts.admin')

@section('title', 'Laporan Penggunaan Bahan Baku')

@section('content')
@php
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);
    $rute  = auth()->user()->role === 'admin' ? 'laporan.penggunaan-bahan' : 'laporan.bahan-baku.penggunaan';

    $adaFilter = filled(request('dari')) || filled(request('sampai'));
    $ikonStok  = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>';
@endphp

<div class="mx-auto max-w-[1400px] print:max-w-none">

    @include('laporan._kepala', [
        'judul'   => 'Laporan Penggunaan Bahan Baku',
        'periode' => 'Periode ' . $dari->translatedFormat('d F Y') . ' sampai ' . $sampai->translatedFormat('d F Y'),
    ])

    {{-- Filter --}}
    <form method="GET" action="{{ route($rute) }}"
          class="mb-3 flex flex-wrap items-start gap-x-4 gap-y-3 rounded-xl bg-white p-3 shadow-sm print:hidden">

        <x-tanggal name="dari" label="Dari tanggal" :value="$dari->toDateString()"
                   :max="$sampai->toDateString()" />
        <x-tanggal name="sampai" label="Sampai tanggal" :value="$sampai->toDateString()"
                   :min="$dari->toDateString()" :max="today()->toDateString()" />

        @if ($adaFilter)
            <div class="self-end">
                <a href="{{ route($rute) }}"
                   class="flex h-9 items-center rounded-full border border-neutral-300 px-4 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50">
                    Reset
                </a>
            </div>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Bahan</th>
                        <th class="px-4 py-2.5 font-semibold">Masuk</th>
                        <th class="px-4 py-2.5 font-semibold">Terpakai (penjualan)</th>
                        <th class="px-4 py-2.5 font-semibold">Rusak</th>
                        <th class="px-4 py-2.5 font-semibold">Kadaluarsa</th>
                        <th class="px-4 py-2.5 font-semibold">Total berkurang</th>
                        <th class="px-4 py-2.5 font-semibold">Stok sekarang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $r)
                        @php
                            $satuan = $r['bahan']->satuan;
                            $kosong = $r['total'] == 0 && $r['masuk'] == 0;
                        @endphp
                        <tr class="border-t border-neutral-100 transition hover:bg-cream/40 {{ $kosong ? 'text-neutral-300' : '' }}">
                            <td class="px-4 py-2 text-[12px] font-semibold {{ $kosong ? 'text-neutral-400' : '' }}">{{ $r['bahan']->nama }}</td>
                            <td class="whitespace-nowrap px-4 py-2 {{ $r['masuk'] > 0 ? 'font-semibold text-green-700' : '' }}">{{ $angka($r['masuk']) }} {{ $satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2">{{ $angka($r['terpakai']) }} {{ $satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2 {{ $r['rusak'] > 0 ? 'text-red-600' : '' }}">{{ $angka($r['rusak']) }} {{ $satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2 {{ $r['kadaluarsa'] > 0 ? 'text-red-600' : '' }}">{{ $angka($r['kadaluarsa']) }} {{ $satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2 font-semibold">{{ $angka($r['total']) }} {{ $satuan }}</td>
                            <td class="whitespace-nowrap px-4 py-2 font-semibold text-neutral-800">{{ $angka($r['bahan']->stok) }} {{ $satuan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonStok !!}</svg>
                                <p class="mt-2 text-xs text-neutral-400">Belum ada bahan baku.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection