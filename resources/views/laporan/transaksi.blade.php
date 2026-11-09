@extends('layouts.admin')

@section('title', 'Laporan Transaksi')

@section('content')
@php
    $rp         = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $namaMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $namaTipe   = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine In'];

    $adaFilter = filled(request('status')) || filled(request('metode')) || filled(request('kasir'))
              || filled(request('dari')) || filled(request('sampai'));

    $ikonNota  = '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>';
    $ikonUsd   = '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.3-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2 0 2.8 5 1.3 5 4 0 1.2-1 2-2.5 2-1.2 0-2.2-.6-2.6-1.5M12 6.5V8M12 16v1.5"/>';
    $ikonBatal = '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>';

    $kartu = [
        ['Jumlah transaksi',         $ringkasan['jumlah'],     $ikonNota,  'bg-cream text-accent',    'text-neutral-900'],
        ['Total penjualan (selesai)', $rp($ringkasan['omset']), $ikonUsd,   'bg-cream text-accent',    'text-neutral-900'],
        ['Dibatalkan',               $ringkasan['batal'],      $ikonBatal, 'bg-red-100 text-red-600', 'text-red-600'],
    ];
@endphp

<div class="mx-auto max-w-[1400px] print:max-w-none">

    @include('laporan._kepala', [
        'judul'   => 'Laporan Transaksi',
        'periode' => 'Periode ' . $dari->translatedFormat('d F Y') . ' sampai ' . $sampai->translatedFormat('d F Y'),
    ])

    {{-- Filter --}}
    <form method="GET" action="{{ route('laporan.transaksi') }}"
          class="mb-3 flex flex-wrap items-start gap-x-4 gap-y-3 rounded-xl bg-white p-3 shadow-sm print:hidden">

        <x-tanggal name="dari" label="Dari tanggal" :value="$dari->toDateString()"
                   :max="$sampai->toDateString()" />
        <x-tanggal name="sampai" label="Sampai tanggal" :value="$sampai->toDateString()"
                   :min="$dari->toDateString()" :max="today()->toDateString()" />

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Status</p>
            <x-pilihan name="status" :options="['selesai' => 'Selesai', 'batal' => 'Batal']"
                       :selected="request('status')" semua="Semua status" />
        </div>

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Metode</p>
            <x-pilihan name="metode" :options="['tunai' => 'Tunai', 'qris' => 'QRIS']"
                       :selected="request('metode')" semua="Semua metode" />
        </div>

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Kasir</p>
            <x-pilihan name="kasir" :options="$daftarKasir->pluck('nama', 'id')->all()"
                       :selected="request('kasir')" semua="Semua kasir" />
        </div>

        @if ($adaFilter)
            <div class="self-end">
                <a href="{{ route('laporan.transaksi') }}"
                   class="flex h-9 items-center rounded-full border border-neutral-300 px-4 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50">
                    Reset
                </a>
            </div>
        @endif
    </form>

    @include('laporan._kartu', ['kartu' => $kartu, 'kolom' => 'grid-cols-1 sm:grid-cols-3'])

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm print:shadow-none print:ring-1 print:ring-neutral-200">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">No. invoice</th>
                        <th class="px-4 py-2.5 font-semibold">Tanggal</th>
                        <th class="px-4 py-2.5 font-semibold">Kasir</th>
                        <th class="px-4 py-2.5 font-semibold">Customer</th>
                        <th class="px-4 py-2.5 font-semibold">Tipe</th>
                        <th class="px-4 py-2.5 font-semibold">Metode</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $t)
                        @php $batal = $t->status === 'batal'; @endphp
                        <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                            <td class="whitespace-nowrap px-4 py-2 font-semibold">{{ $t->no_invoice }}</td>
                            <td class="whitespace-nowrap px-4 py-2">
                                {{ $t->tanggal_transaksi->format('d/m/Y') }}
                                <span class="text-[10px] text-neutral-400">{{ $t->tanggal_transaksi->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-2">{{ $t->user->nama }}</td>
                            <td class="px-4 py-2 {{ $t->nama_customer ? '' : 'text-neutral-300' }}">{{ $t->nama_customer ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-2">{{ $namaTipe[$t->tipe_pesanan] ?? $t->tipe_pesanan }}</td>
                            <td class="px-4 py-2">{{ $namaMetode[$t->pembayaran->first()?->metode] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-right font-semibold {{ $batal ? 'text-neutral-400 line-through' : '' }}">{{ $rp($t->total) }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $batal ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-800' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $batal ? 'bg-red-600' : 'bg-green-600' }}"></span>
                                    {{ $batal ? 'Batal' : 'Selesai' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonNota !!}</svg>
                                <p class="mt-2 text-xs text-neutral-400">Tidak ada transaksi pada periode ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 print:hidden">
        @if ($semua)
            <p class="text-[11px] text-neutral-500">Menampilkan semua baris (maksimal 2.000).</p>
            <a href="{{ request()->fullUrlWithQuery(['semua' => null]) }}"
               class="text-[11px] font-semibold text-accent hover:underline">Kembali ke tampilan per halaman</a>
        @else
            <div>{{ $daftar->withQueryString()->links('vendor.pagination.custom') }}</div>
            <a href="{{ request()->fullUrlWithQuery(['semua' => 1]) }}"
               class="text-[11px] font-semibold text-accent hover:underline">Tampilkan semua baris (untuk dicetak)</a>
        @endif
    </div>
</div>
@endsection