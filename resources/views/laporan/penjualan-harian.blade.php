@extends('layouts.admin')

@section('title', 'Laporan Penjualan Harian')

@section('content')
@php
    $rp         = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $namaMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $namaTipe   = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine In'];

    $ikonUsd    = '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.3-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2 0 2.8 5 1.3 5 4 0 1.2-1 2-2.5 2-1.2 0-2.2-.6-2.6-1.5M12 6.5V8M12 16v1.5"/>';
    $ikonNota   = '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>';
    $ikonStok   = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>';
    $ikonPersen = '<path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>';
    $ikonBatal  = '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>';

    $kartu = [
        ['Total penjualan',  $rp($ringkasan['omset']),    $ikonUsd,    'bg-cream text-accent',    'text-neutral-900'],
        ['Jumlah transaksi', $ringkasan['jumlah'],        $ikonNota,   'bg-cream text-accent',    'text-neutral-900'],
        ['Subtotal',         $rp($ringkasan['subtotal']), $ikonStok,   'bg-cream text-accent',    'text-neutral-900'],
        ['Pajak',            $rp($ringkasan['pajak']),    $ikonPersen, 'bg-cream text-accent',    'text-neutral-900'],
        ['Dibatalkan',       $ringkasan['batal'],         $ikonBatal,  'bg-red-100 text-red-600', 'text-red-600'],
    ];
@endphp

<div class="mx-auto max-w-[1400px] print:max-w-none">

    @include('laporan._kepala', [
        'judul'   => 'Laporan Penjualan Harian',
        'periode' => 'Tanggal ' . $tanggal->translatedFormat('d F Y'),
        'filterRingkas' => [
            'name'  => 'tanggal',
            'label' => 'Tanggal',
            'value' => $tanggal->toDateString(),
            'max'   => today()->toDateString(),
            'aktif' => ! $tanggal->isToday(),
        ],
    ])

    @include('laporan._kartu', ['kartu' => $kartu, 'kolom' => 'grid-cols-2 lg:grid-cols-5'])

    {{-- Metode dan tipe --}}
    <div class="mb-3 grid gap-3 md:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
            <p class="mb-2 text-[13px] font-semibold">Per metode pembayaran</p>
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="rounded-l-lg px-3 py-2 font-semibold">Metode</th>
                        <th class="px-3 py-2 text-center font-semibold">Transaksi</th>
                        <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metode as $m)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2 font-medium">{{ $namaMetode[$m->metode] ?? $m->metode }}</td>
                            <td class="px-3 py-2 text-center">{{ $m->jumlah }}</td>
                            <td class="px-3 py-2 text-right">{{ $rp($m->omset) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-neutral-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
            <p class="mb-2 text-[13px] font-semibold">Per tipe pesanan</p>
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="rounded-l-lg px-3 py-2 font-semibold">Tipe</th>
                        <th class="px-3 py-2 text-center font-semibold">Transaksi</th>
                        <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tipe as $t)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2 font-medium">{{ $namaTipe[$t->tipe_pesanan] ?? $t->tipe_pesanan }}</td>
                            <td class="px-3 py-2 text-center">{{ $t->jumlah }}</td>
                            <td class="px-3 py-2 text-right">{{ $rp($t->omset) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-neutral-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Menu terjual --}}
    <div class="rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <p class="mb-2 text-[13px] font-semibold">Menu terjual</p>
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="rounded-l-lg px-3 py-2 font-semibold">Menu</th>
                        <th class="px-3 py-2 text-center font-semibold">Jumlah terjual</th>
                        <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Penjualan (sebelum pajak)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menu as $m)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2 font-medium">{{ $m->nama }}</td>
                            <td class="px-3 py-2 text-center">{{ (int) $m->terjual }}</td>
                            <td class="px-3 py-2 text-right">{{ $rp($m->omset) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-10 text-center text-neutral-400">Tidak ada penjualan pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($menu->isNotEmpty())
                    <tfoot>
                        <tr class="border-t border-neutral-300 bg-cream/50 font-semibold">
                            <td class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-center">{{ (int) $menu->sum('terjual') }}</td>
                            <td class="px-3 py-2 text-right">{{ $rp($menu->sum('omset')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection