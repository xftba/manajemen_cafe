@extends('layouts.admin')

@section('title', 'Detail Supplier')

@section('content')
@php
    $rp    = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);
    $bulanIni = now()->format('Y-m');
    $label = \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y');

    $ikonKirim = '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>';
    $ikonUsd   = '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.3-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2 0 2.8 5 1.3 5 4 0 1.2-1 2-2.5 2-1.2 0-2.2-.6-2.6-1.5M12 6.5V8M12 16v1.5"/>';
    $ikonStok  = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>';

    $kartu = [
        ['Pengiriman',    $ringkasan['kali_kirim'] . 'x',          $ikonKirim, 'bg-cream text-accent', 'text-neutral-900'],
        ['Total nilai',   $rp($ringkasan['total_nilai']),          $ikonUsd,   'bg-cream text-accent', 'text-neutral-900'],
        ['Jenis bahan',   $ringkasan['jenis_bahan'],               $ikonStok,  'bg-cream text-accent', 'text-neutral-900'],
    ];
@endphp

<div class="mx-auto max-w-[1400px]">

    {{-- Kepala --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('supplier.index', ['bulan' => $bulan]) }}" aria-label="Kembali"
               class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-200/80 text-neutral-600 transition hover:bg-neutral-300/70">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            </a>
            <div class="min-w-0">
                <h2 class="truncate text-[15px] font-semibold leading-tight">{{ $supplier->nama }}</h2>
                <p class="truncate text-[11px] text-neutral-500">
                    {{ $supplier->telepon ?: 'Telepon belum diisi' }}
                    @if ($supplier->alamat) • {{ $supplier->alamat }} @endif
                </p>
            </div>
        </div>

        <form method="GET" class="flex shrink-0 items-end gap-2">
            <x-tanggal type="month" name="bulan" label="Bulan" :value="$bulan"
                       :max="$bulanIni" :aktif="$bulan !== $bulanIni" />
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($kartu as [$judul, $nilai, $ikon, $warnaIkon, $warnaAngka])
            <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] text-neutral-500">{{ $judul }} ({{ $label }})</p>
                    <p class="truncate text-xl font-semibold leading-tight {{ $warnaAngka }}">{{ $nilai }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-3 lg:grid-cols-2">

        {{-- Bahan yang dipasok --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <p class="px-4 pb-1 pt-3 text-[13px] font-semibold">Bahan yang dipasok</p>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Bahan</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Jumlah masuk</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Harga rata-rata</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bahan as $b)
                            <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                                <td class="px-4 py-2 font-semibold">{{ $b->nama }}</td>
                                <td class="whitespace-nowrap px-4 py-2 text-right">{{ $angka($b->total_jumlah) }} {{ $b->satuan }}</td>
                                <td class="whitespace-nowrap px-4 py-2 text-right text-neutral-500">
                                    {{ $b->total_jumlah > 0 ? $rp($b->total_nilai / $b->total_jumlah) : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-right font-semibold">{{ $rp($b->total_nilai) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-xs text-neutral-400">Tidak ada barang masuk pada {{ $label }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat pengiriman --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <p class="px-4 pb-1 pt-3 text-[13px] font-semibold">Riwayat pengiriman</p>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Jenis bahan</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $r)
                            @php $t = \Carbon\Carbon::parse($r->tanggal); @endphp
                            <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">
                                <td class="whitespace-nowrap px-4 py-2">
                                    <p class="font-semibold">{{ $t->format('d/m/Y') }}</p>
                                    <p class="text-[10px] text-neutral-400">{{ $t->format('H:i') }}</p>
                                    @if ($r->catatan)
                                        <p class="mt-0.5 max-w-[200px] truncate text-[10px] text-neutral-500" title="{{ $r->catatan }}">{{ $r->catatan }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">{{ $r->jenis_bahan }}</td>
                                <td class="whitespace-nowrap px-4 py-2 text-right font-semibold">{{ $rp($r->nilai) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-10 text-center text-xs text-neutral-400">Belum ada pengiriman pada {{ $label }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection