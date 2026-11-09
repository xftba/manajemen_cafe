@extends('layouts.kasir')

@section('title', 'Analitik & Omset')

@section('content')
@php
    $rp         = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $namaMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $namaTipe   = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine In'];

    $hari = today()->toDateString();
    $preset = [
        'Hari ini'        => ['dari' => $hari, 'sampai' => $hari],
        '7 hari terakhir' => ['dari' => today()->subDays(6)->toDateString(), 'sampai' => $hari],
        'Bulan ini'       => ['dari' => today()->startOfMonth()->toDateString(), 'sampai' => $hari],
    ];

    $labelHarian = $harian->map(fn ($h) => $h['tanggal']->translatedFormat('d M'))->values()->all();
    $dataHarian  = $harian->map(fn ($h) => $h['omset'])->values()->all();
    $labelMetode = $metode->map(fn ($m) => $namaMetode[$m->metode] ?? $m->metode)->values()->all();
    $dataMetode  = $metode->map(fn ($m) => (float) $m->omset)->values()->all();
@endphp

<div class="p-6">
    <div>
        <p class="mb-4 text-sm text-neutral-500">Menampilkan transaksi Anda sendiri. Omset hanya menghitung transaksi yang berstatus selesai.</p>

        {{-- Filter periode --}}
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-sm">
            <div class="flex flex-wrap gap-2">
                @foreach ($preset as $nama => $p)
                    @php $aktif = $dari->toDateString() === $p['dari'] && $sampai->toDateString() === $p['sampai']; @endphp
                    <a href="{{ route('kasir.analitik', $p) }}"
                       class="rounded-full px-4 py-2 text-sm font-medium transition {{ $aktif ? 'bg-primary text-white' : 'bg-neutral-200 text-neutral-600 hover:bg-neutral-300/70' }}">
                        {{ $nama }}
                    </a>
                @endforeach
            </div>

            <form method="GET" class="ml-auto flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs text-neutral-500">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ $dari->toDateString() }}"
                           class="h-11 rounded-xl border border-neutral-300 px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-neutral-500">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ $sampai->toDateString() }}"
                           class="h-11 rounded-xl border border-neutral-300 px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button class="h-11 rounded-xl bg-primary px-6 font-semibold text-white hover:opacity-90">Tampilkan</button>
            </form>
        </div>

        {{-- Statistik --}}
        <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs text-neutral-500">Omset</p>
                <p class="mt-1 text-2xl font-semibold">{{ $rp($ringkasan['omset']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs text-neutral-500">Jumlah transaksi</p>
                <p class="mt-1 text-2xl font-semibold">{{ $ringkasan['jumlah'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs text-neutral-500">Rata-rata per transaksi</p>
                <p class="mt-1 text-2xl font-semibold">{{ $rp($ringkasan['rata']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs text-neutral-500">Dibatalkan</p>
                <p class="mt-1 text-2xl font-semibold text-red-700">{{ $ringkasan['batal'] }}</p>
            </div>
        </div>

        {{-- Grafik --}}
        <div class="mb-4 grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm xl:col-span-2">
                <p class="mb-3 font-semibold">Omset per hari</p>
                <div class="h-64"><canvas id="grafik-harian"></canvas></div>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold">Metode pembayaran</p>
                @if ($metode->isEmpty())
                    <p class="py-16 text-center text-sm text-neutral-400">Belum ada transaksi pada periode ini.</p>
                @else
                    <div class="h-64"><canvas id="grafik-metode"></canvas></div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm xl:col-span-2">
                <p class="mb-3 font-semibold">Menu terlaris</p>
                <table class="w-full text-sm">
                    <thead class="bg-cream text-left">
                        <tr>
                            <th class="px-3 py-2">Menu</th>
                            <th class="px-3 py-2">Terjual</th>
                            <th class="px-3 py-2">Penjualan (sebelum pajak)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($terlaris as $m)
                            <tr class="border-t border-neutral-100">
                                <td class="px-3 py-2 font-medium">{{ $m->nama }}</td>
                                <td class="px-3 py-2">{{ (int) $m->terjual }}</td>
                                <td class="px-3 py-2">{{ $rp($m->omset) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-8 text-center text-neutral-400">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold">Tipe pesanan</p>
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($tipe as $t)
                            <tr class="border-t border-neutral-100 first:border-0">
                                <td class="py-2 font-medium">{{ $namaTipe[$t->tipe_pesanan] ?? $t->tipe_pesanan }}</td>
                                <td class="py-2 text-right">
                                    <p class="font-semibold">{{ $rp($t->omset) }}</p>
                                    <p class="text-xs text-neutral-500">{{ $t->jumlah }} transaksi</p>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-8 text-center text-neutral-400">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    grafikGaris('grafik-harian', @js($labelHarian), @js($dataHarian));
    grafikDonat('grafik-metode', @js($labelMetode), @js($dataMetode));
});
</script>
@endpush