@extends('layouts.admin')

@section('title', 'Riwayat Pergerakan Stok')

@section('content')
@php
    $admin = auth()->user()->role === 'admin';
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
    $titik = [
        'stok_awal'   => 'bg-neutral-500',
        'masuk'       => 'bg-green-600',
        'keluar'      => 'bg-blue-600',
        'pembatalan'  => 'bg-amber-600',
        'rusak'       => 'bg-red-600',
        'kadaluarsa'  => 'bg-red-600',
        'penyesuaian' => 'bg-purple-600',
    ];

    $opsiBahan = $bahan->pluck('nama', 'id')->all();
    $opsiTipe  = \App\Models\PergerakanStok::LABEL;

    $adaFilter = request()->hasAny(['bahan', 'tipe', 'dari', 'sampai']) &&
                 (request('bahan') || request('tipe') || request('dari') || request('sampai'));

    $ikonRiwayat = '<path d="M4 12a8 8 0 108-8 8 8 0 00-5.7 2.3L4 8"/><path d="M4 4v4h4M12 8v4l3 2"/>';

    $kelasTanggal = 'h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm text-neutral-700 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
@endphp

<div class="mx-auto max-w-[1400px]">

{{-- Filter --}}
<form method="GET" action="{{ route('pergerakan-stok.index') }}" x-data
      class="mb-3 flex flex-wrap items-start gap-x-4 gap-y-3 rounded-xl bg-white p-3 shadow-sm">

    {{-- Bahan baku --}}
    <div>
        <p class="mb-1 text-[10px] font-medium text-neutral-500">Bahan baku</p>
        <x-pilihan name="bahan" :options="$opsiBahan" :selected="request('bahan')" semua="Semua bahan" />
    </div>

    {{-- Jenis --}}
    <div>
        <p class="mb-1 text-[10px] font-medium text-neutral-500">Jenis</p>
        <x-pilihan name="tipe" :options="$opsiTipe" :selected="request('tipe')" semua="Semua jenis" />
    </div>

    {{-- Dari tanggal --}}
    <div>
        <p class="mb-1 text-[10px] font-medium text-neutral-500">Dari tanggal</p>
        <label class="flex h-9 items-center rounded-full px-4 transition
                      {{ request('dari') ? 'bg-accent text-white shadow-sm' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200/70' }}">
            <input type="date" name="dari" value="{{ request('dari') }}"
                   max="{{ request('sampai') ?: today()->toDateString() }}"
                   @change="$el.form.submit()"
                   class="bg-transparent text-[11px] font-medium outline-none {{ request('dari') ? '[color-scheme:dark]' : '' }}">
        </label>
    </div>

    {{-- Sampai tanggal --}}
    <div>
        <p class="mb-1 text-[10px] font-medium text-neutral-500">Sampai tanggal</p>
        <label class="flex h-9 items-center rounded-full px-4 transition
                      {{ request('sampai') ? 'bg-accent text-white shadow-sm' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200/70' }}">
            <input type="date" name="sampai" value="{{ request('sampai') }}"
                   min="{{ request('dari') }}" max="{{ today()->toDateString() }}"
                   @change="$el.form.submit()"
                   class="bg-transparent text-[11px] font-medium outline-none {{ request('sampai') ? '[color-scheme:dark]' : '' }}">
        </label>
    </div>

    {{-- Reset --}}
    @if ($adaFilter)
        <div class="self-end">
            <a href="{{ route('pergerakan-stok.index') }}"
               class="flex h-9 items-center rounded-full border border-neutral-300 px-4 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50">
                Reset
            </a>
        </div>
    @endif
</form>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Waktu</th>
                        <th class="px-4 py-2.5 font-semibold">Bahan</th>
                        <th class="px-4 py-2.5 font-semibold">Jenis</th>
                        <th class="px-4 py-2.5 font-semibold">Perubahan</th>
                        <th class="px-4 py-2.5 font-semibold">Stok</th>
                        <th class="px-4 py-2.5 font-semibold">Referensi</th>
                        <th class="px-4 py-2.5 font-semibold">Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pergerakan as $p)
                        @php $selisih = $p->selisih; @endphp
                        <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">
                            <td class="whitespace-nowrap px-4 py-2">
                                <p class="font-semibold">{{ $p->created_at->format('d/m/Y') }}</p>
                                <p class="text-[10px] text-neutral-400">{{ $p->created_at->format('H:i') }}</p>
                            </td>

                            <td class="px-4 py-2 text-[12px] font-semibold">{{ $p->bahanBaku->nama }}</td>

                            <td class="px-4 py-2">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $warna[$p->tipe_pergerakan] ?? 'bg-neutral-100 text-neutral-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $titik[$p->tipe_pergerakan] ?? 'bg-neutral-500' }}"></span>
                                    {{ $p->label }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-2 font-semibold {{ $selisih >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ $selisih >= 0 ? '+' : '−' }}{{ $angka(abs($selisih)) }} {{ $p->bahanBaku->satuan }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-2 text-neutral-500">
                                {{ $angka($p->stok_sebelum) }} <span class="text-neutral-300">→</span>
                                <span class="font-semibold text-neutral-800">{{ $angka($p->stok_sesudah) }}</span>
                            </td>

                            <td class="px-4 py-2">
                                @if ($p->transaksi)
                                    @if ($admin)
                                        <a href="{{ route('transaksi.show', $p->transaksi) }}" class="font-semibold text-accent hover:underline">{{ $p->transaksi->no_invoice }}</a>
                                    @else
                                        <span class="font-semibold">{{ $p->transaksi->no_invoice }}</span>
                                    @endif
                                @elseif ($p->barangMasuk)
                                    @if (! $admin)
                                        <a href="{{ route('barang-masuk.show', $p->barangMasuk) }}" class="font-semibold text-accent hover:underline">{{ $p->barangMasuk->kode }}</a>
                                    @else
                                        <span class="font-semibold">{{ $p->barangMasuk->kode }}</span>
                                    @endif
                                @elseif ($p->penyesuaian_stok_id)
                                    <span class="font-semibold">Penyesuaian #{{ $p->penyesuaian_stok_id }}</span>
                                @else
                                    <span class="text-neutral-400">-</span>
                                @endif

                                @if ($p->catatan)
                                    <p class="mt-0.5 max-w-[240px] truncate text-[10px] text-neutral-500" title="{{ $p->catatan }}">{{ $p->catatan }}</p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-2">{{ $p->user->nama }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonRiwayat !!}</svg>
                                <p class="mt-2 text-xs text-neutral-400">Belum ada pergerakan stok yang cocok.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 border-t border-neutral-100 pt-4">
        {{ $pergerakan->withQueryString()->links('vendor.pagination.custom') }}
    </div>
</div>
@endsection