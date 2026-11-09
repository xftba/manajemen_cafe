@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@section('content')
@php
    $warnaAksi = [
        'tambah'         => ['bg-green-100 text-green-800',   'bg-green-600'],
        'aktifkan'       => ['bg-green-100 text-green-800',   'bg-green-600'],
        'ubah'           => ['bg-blue-100 text-blue-800',     'bg-blue-600'],
        'hapus'          => ['bg-red-100 text-red-800',       'bg-red-600'],
        'batal'          => ['bg-red-100 text-red-800',       'bg-red-600'],
        'nonaktifkan'    => ['bg-red-100 text-red-800',       'bg-red-600'],
        'login gagal'    => ['bg-red-100 text-red-800',       'bg-red-600'],
        'tolak'          => ['bg-red-100 text-red-800',       'bg-red-600'],
        'ajukan'         => ['bg-amber-100 text-amber-800',   'bg-amber-600'],
        'reset password' => ['bg-amber-100 text-amber-800',   'bg-amber-600'],
        'minta restock'  => ['bg-amber-100 text-amber-800',   'bg-amber-600'],
        'setujui'        => ['bg-purple-100 text-purple-800', 'bg-purple-600'],
        'ekspor'         => ['bg-neutral-100 text-neutral-700', 'bg-neutral-500'],
        'login'          => ['bg-neutral-100 text-neutral-700', 'bg-neutral-500'],
        'logout'         => ['bg-neutral-100 text-neutral-700', 'bg-neutral-500'],
    ];
    $warnaDefault = ['bg-neutral-100 text-neutral-700', 'bg-neutral-500'];

    $opsiPengguna = $pengguna->pluck('nama', 'id')->all();
    $opsiModul    = collect($daftarModul)->mapWithKeys(fn ($m) => [$m => ucfirst($m)])->all();
    $opsiAksi     = collect($daftarAksi)->mapWithKeys(fn ($a) => [$a => ucfirst($a)])->all();

    $adaFilter = filled(request('pengguna')) || filled(request('modul')) || filled(request('aksi'))
              || filled(request('dari')) || filled(request('sampai')) || filled(request('q'));

    $ikonLog = '<path d="M9 4h6v2H9z"/><rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 11h6M9 15h4"/>';
@endphp

<div class="mx-auto max-w-[1400px]">

    {{-- Filter --}}
    <form method="GET" action="{{ route('log-aktivitas.index') }}" x-data
          class="mb-3 flex flex-wrap items-start gap-x-4 gap-y-3 rounded-xl bg-white p-3 shadow-sm">

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Pengguna</p>
            <x-pilihan name="pengguna" :options="$opsiPengguna" :selected="request('pengguna')" semua="Semua pengguna" />
        </div>

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Modul</p>
            <x-pilihan name="modul" :options="$opsiModul" :selected="request('modul')" semua="Semua modul" />
        </div>

        <div class="shrink-0">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Aksi</p>
            <x-pilihan name="aksi" :options="$opsiAksi" :selected="request('aksi')" semua="Semua aksi" />
        </div>

        <x-tanggal name="dari" label="Dari tanggal" :value="request('dari')"
                   :max="request('sampai') ?: today()->toDateString()" :aktif="filled(request('dari'))" />

        <x-tanggal name="sampai" label="Sampai tanggal" :value="request('sampai')"
                   :min="request('dari')" :max="today()->toDateString()" :aktif="filled(request('sampai'))" />

        <div class="min-w-[180px] flex-1">
            <p class="mb-1 text-[10px] font-medium text-neutral-500">Cari keterangan</p>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Kata kunci..." autocomplete="off"
                       x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                       @input.debounce.500ms="$el.form.submit()"
                       class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
        </div>

        @if ($adaFilter)
            <div class="self-end">
                <a href="{{ route('log-aktivitas.index') }}"
                   class="flex h-9 items-center rounded-full border border-neutral-300 px-4 text-[11px] font-semibold text-neutral-600 transition hover:bg-neutral-50">
                    Reset
                </a>
            </div>
        @endif
    </form>

    <p class="mb-2 text-[11px] text-neutral-500">{{ number_format($log->total(), 0, ',', '.') }} catatan ditemukan.</p>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead class="bg-cream text-left text-accent">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Waktu</th>
                        <th class="px-4 py-2.5 font-semibold">Pengguna</th>
                        <th class="px-4 py-2.5 font-semibold">Aksi</th>
                        <th class="px-4 py-2.5 font-semibold">Modul</th>
                        <th class="px-4 py-2.5 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($log as $item)
                        @php [$kelas, $titik] = $warnaAksi[$item->aksi] ?? $warnaDefault; @endphp
                        <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">

                            <td class="whitespace-nowrap px-4 py-2">
                                <p class="font-semibold">{{ $item->created_at->format('d/m/Y') }}</p>
                                <p class="text-[10px] text-neutral-400">{{ $item->created_at->format('H:i:s') }}</p>
                            </td>

                            <td class="px-4 py-2">
                                @if ($item->user)
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent text-[11px] font-semibold text-white">
                                            {{ strtoupper(substr($item->user->nama, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-[12px] font-semibold">{{ $item->user->nama }}</p>
                                            <p class="text-[10px] text-neutral-400">{{ $item->user->role_label }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-neutral-400">Tidak dikenal</span>
                                @endif
                            </td>

                            <td class="px-4 py-2">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $kelas }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $titik }}"></span>
                                    {{ ucfirst($item->aksi) }}
                                </span>
                            </td>

                            <td class="px-4 py-2">
                                <span class="inline-block rounded-full bg-cream px-2.5 py-0.5 text-[10px] font-medium text-accent">
                                    {{ ucfirst($item->modul) }}
                                </span>
                            </td>

                            <td class="max-w-[420px] px-4 py-2 text-neutral-700 {{ $item->deskripsi ? '' : 'text-neutral-300' }}">
                                {{ $item->deskripsi ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonLog !!}</svg>
                                <p class="mt-2 text-xs text-neutral-400">Belum ada catatan aktivitas yang cocok.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $log->withQueryString()->links('vendor.pagination.custom') }}</div>
</div>
@endsection