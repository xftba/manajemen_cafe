@extends('layouts.admin')

@section('title', 'Laporan Penjualan Bulanan')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    $labelGrafik = $harian->map(fn ($h) => $h['tanggal']->format('j'))->values()->all();
    $dataGrafik  = $harian->map(fn ($h) => $h['omset'])->values()->all();

    $ikonUsd    = '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.3-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2 0 2.8 5 1.3 5 4 0 1.2-1 2-2.5 2-1.2 0-2.2-.6-2.6-1.5M12 6.5V8M12 16v1.5"/>';
    $ikonNota   = '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>';
    $ikonRata   = '<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>';
    $ikonPersen = '<path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>';
    $ikonBatal  = '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>';

    $kartu = [
        ['Total penjualan',       $rp($ringkasan['omset']), $ikonUsd,    'bg-cream text-accent',    'text-neutral-900'],
        ['Jumlah transaksi',      $ringkasan['jumlah'],     $ikonNota,   'bg-cream text-accent',    'text-neutral-900'],
        ['Rata-rata / transaksi', $rp($ringkasan['rata']),  $ikonRata,   'bg-cream text-accent',    'text-neutral-900'],
        ['Pajak',                 $rp($ringkasan['pajak']), $ikonPersen, 'bg-cream text-accent',    'text-neutral-900'],
        ['Dibatalkan',            $ringkasan['batal'],      $ikonBatal,  'bg-red-100 text-red-600', 'text-red-600'],
    ];
@endphp

<div class="mx-auto max-w-[1400px] print:max-w-none">

    @include('laporan._kepala', [
        'judul'   => 'Laporan Penjualan Bulanan',
        'periode' => 'Bulan ' . $awal->translatedFormat('F Y'),
        'filterRingkas' => [
            'type'  => 'month',
            'name'  => 'bulan',
            'label' => 'Bulan',
            'value' => $awal->format('Y-m'),
            'max'   => today()->format('Y-m'),
            'aktif' => $awal->format('Y-m') !== today()->format('Y-m'),
        ],
    ])

    @include('laporan._kartu', ['kartu' => $kartu, 'kolom' => 'grid-cols-2 lg:grid-cols-5'])

    {{-- Grafik --}}
    <div class="mb-3 rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <p class="mb-2 text-[13px] font-semibold">Penjualan per hari</p>
        <div class="h-52"><canvas id="grafik-bulanan"></canvas></div>
    </div>

    {{-- Rincian per hari --}}
    <div class="mb-3 rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <p class="mb-2 text-[13px] font-semibold">Rincian per hari</p>
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="rounded-l-lg px-3 py-2 font-semibold">Tanggal</th>
                        <th class="px-3 py-2 text-center font-semibold">Transaksi</th>
                        <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                        <th class="px-3 py-2 text-right font-semibold">Pajak</th>
                        <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Total penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($harian as $h)
                        <tr class="border-t border-neutral-100 {{ $h['jumlah'] === 0 ? 'text-neutral-300' : '' }}">
                            <td class="whitespace-nowrap px-3 py-1.5">{{ $h['tanggal']->translatedFormat('l, d F Y') }}</td>
                            <td class="px-3 py-1.5 text-center">{{ $h['jumlah'] }}</td>
                            <td class="px-3 py-1.5 text-right">{{ $rp($h['subtotal']) }}</td>
                            <td class="px-3 py-1.5 text-right">{{ $rp($h['pajak']) }}</td>
                            <td class="px-3 py-1.5 text-right font-semibold">{{ $rp($h['omset']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-neutral-300 bg-cream/50 font-semibold">
                        <td class="px-3 py-2">Total</td>
                        <td class="px-3 py-2 text-center">{{ $ringkasan['jumlah'] }}</td>
                        <td class="px-3 py-2 text-right">{{ $rp($ringkasan['subtotal']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $rp($ringkasan['pajak']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $rp($ringkasan['omset']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Menu terlaris --}}
    <div class="rounded-xl bg-white p-4 shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <p class="mb-2 text-[13px] font-semibold">10 menu terlaris</p>
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="rounded-l-lg px-3 py-2 font-semibold">Menu</th>
                        <th class="px-3 py-2 text-center font-semibold">Terjual</th>
                        <th class="rounded-r-lg px-3 py-2 text-right font-semibold">Penjualan (sebelum pajak)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menu as $i => $m)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2 font-medium"><span class="mr-1.5 text-neutral-400">{{ $i + 1 }}.</span>{{ $m->nama }}</td>
                            <td class="px-3 py-2 text-center">{{ (int) $m->terjual }}</td>
                            <td class="px-3 py-2 text-right">{{ $rp($m->omset) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-10 text-center text-neutral-400">Tidak ada penjualan pada bulan ini.</td></tr>
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
    grafikBatang('grafik-bulanan', @js($labelGrafik), @js($dataGrafik));
});
</script>
@endpush