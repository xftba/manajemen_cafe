@extends(auth()->user()->role === 'kasir' ? 'layouts.kasir' : 'layouts.admin')

@section('title', 'Riwayat Transaksi')

@section('content')
@php
    $kasir      = auth()->user()->role === 'kasir';
    $rp         = fn ($n) => number_format($n, 0, ',', '.');
    $hariIni    = $tanggal === today()->toDateString();
    $namaMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    $namaTipe   = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine In'];

    // Ikon (SVG inline, isi saja tanpa tag svg)
    $ikonQr   = '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h3M21 17v4"/>';
    $ikonUang = '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 10v.01M18 14v.01"/>';
    $ikonX    = '<circle cx="12" cy="12" r="8"/><path d="M9 9l6 6M15 9l-6 6"/>';
    $ikonNota = '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>';
    $ikonUsd  = '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.4-.9-1.3-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2 0 2.8 5 1.3 5 4 0 1.2-1 2-2.5 2-1.2 0-2.2-.6-2.6-1.5M12 6.5V8M12 16v1.5"/>';

    $kartu = [
        ['judul' => 'Total Transaksi', 'nilai' => number_format($ringkasan['jumlah'], 0, ',', '.'), 'satuan' => 'Pesanan', 'rp' => false, 'ikon' => $ikonNota],
        ['judul' => $hariIni ? 'Omset Hari Ini' : 'Omset', 'nilai' => $rp($ringkasan['omset']), 'satuan' => null, 'rp' => true, 'ikon' => $ikonUsd],
        ['judul' => 'Tunai', 'nilai' => $rp($ringkasan['tunai']), 'satuan' => null, 'rp' => true, 'ikon' => $ikonUang],
        ['judul' => 'QRIS', 'nilai' => $rp($ringkasan['qris']), 'satuan' => null, 'rp' => true, 'ikon' => $ikonQr],
    ];

    $chips = [
        '' => ['Semua (' . $ringkasan['jumlah'] . ')', '<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="1"/><circle cx="16" cy="12" r="1"/><circle cx="10" cy="18" r="1"/>'],
        'qris'  => ['QRIS', $ikonQr],
        'tunai' => ['Tunai', $ikonUang],
        'batal' => ['Dibatalkan', $ikonX],
    ];
    $paramChip = request()->except(['page', 'detail', 'filter']);

    // "Hari ini, 14.40", "Kemarin, 09.15", atau "05/11/2026, 16.05"
    $waktu = function ($t) {
        $jam = $t->format('H.i');

        if ($t->isToday()) {
            return "Hari ini, {$jam}";
        }

        return $t->isYesterday() ? "Kemarin, {$jam}" : $t->format('d/m/Y') . ", {$jam}";
    };

    // "2 Item • 1x Latte Ice Small Normal Sugar, 1x Mocha ..."
    $teksItem = function ($t) {
        $isi = $t->detail->take(2)->map(function ($d) {
            $varian = $d->varian ? ' ' . str_replace(' • ', ' ', $d->varian) : '';

            return $d->jumlah . 'x ' . ($d->menu?->nama ?? 'Menu') . $varian;
        })->implode(', ');

        $lebih = $t->detail->count() - 2;

        return $t->detail->sum('jumlah') . ' Item • ' . $isi . ($lebih > 0 ? " • +{$lebih} menu lainnya" : '');
    };

    $pembayaran   = $dipilih?->pembayaran->first();
    $tanggalAktif = request()->filled('tanggal') && ! $hariIni;
@endphp

<div x-data="{ batal: @js($errors->has('alasan')), kirim: false }"
     @keydown.escape.window="batal = false"
     class="{{ $kasir ? 'px-3 pb-3 pt-3' : 'mx-auto max-w-[1400px]' }}">

    <div class="grid grid-cols-1 items-start gap-3 xl:grid-cols-[minmax(0,1fr)_300px]">

        {{-- ================= KIRI: DAFTAR TRANSAKSI ================= --}}
        <section class="min-w-0">

            {{-- ================= KARTU RINGKASAN ================= --}}
            <div class="mb-3 grid grid-cols-2 gap-2.5 xl:grid-cols-4">
                @foreach ($kartu as $k)
                    <div class="rounded-xl bg-white p-3 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[10px] font-semibold uppercase tracking-wide">
                                {{ $k['judul'] }}
                            </p>

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-cream text-accent">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    {!! $k['ikon'] !!}
                                </svg>
                            </span>
                        </div>

                        <p class="mt-1.5 flex items-baseline gap-1">
                            @if ($k['rp'])
                                <span class="text-[10px] text-neutral-400">Rp</span>
                            @endif

                            <span class="text-lg font-bold leading-none">
                                {{ $k['nilai'] }}
                            </span>

                            @if ($k['satuan'])
                                <span class="text-[10px] text-neutral-400">
                                    {{ $k['satuan'] }}
                                </span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Kolom cari (admin saja, kasir memakai bilah atas) --}}
            @unless ($kasir)
                <form method="GET" action="{{ route($rute['index']) }}" class="relative mb-3 max-w-[400px]">
                    @foreach (\Illuminate\Support\Arr::except(request()->query(), ['q', 'page', 'detail']) as $nama => $nilai)
                        @if (is_scalar($nilai) && $nilai !== '')
                            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                        @endif
                    @endforeach
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari invoice, customer, atau menu..." autocomplete="off"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </form>
            @endunless

            {{-- Tanggal, kasir, dan chip filter (satu baris, bisa digeser) --}}
            <div class="no-scrollbar -mx-1 mb-3 flex flex-nowrap items-center gap-2 overflow-x-auto px-1 pb-1">

                <form method="GET" action="{{ route($rute['index']) }}" x-data
                      class="flex shrink-0 flex-nowrap items-center gap-2">
                    @if ($filter)
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif

                    @if (filled(request('q')))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif

                    <label class="flex h-9 shrink-0 items-center rounded-full px-4 transition
                                  {{ $tanggalAktif ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-500 hover:bg-neutral-300/70' }}">
                        <input
                            type="date"
                            name="tanggal"
                            value="{{ $tanggal }}"
                            max="{{ today()->toDateString() }}"
                            onchange="this.form.submit()"
                            class="bg-transparent text-[11px] font-medium outline-none {{ $tanggalAktif ? '[color-scheme:dark]' : '' }}"
                        >
                    </label>

                    @unless ($kasir)
                        <x-pilihan name="kasir"
                                   :options="$daftarKasir->pluck('nama', 'id')->all()"
                                   :selected="request('kasir')"
                                   semua="Semua kasir" />
                    @endunless
                </form>

                @foreach ($chips as $kode => [$label, $ikon])
                    @php $aktifChip = ($filter ?? '') === (string) $kode; @endphp

                    <a href="{{ route($rute['index'], array_merge($paramChip, ['filter' => $kode ?: null])) }}"
                       class="flex h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full pl-[6px] pr-4 text-[11px] font-semibold transition
                       {{ $aktifChip ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-400 hover:bg-neutral-300/70' }}">

                        @if ($ikon)
                            <span class="flex h-[20px] w-[20px] items-center justify-center rounded-full
                                {{ $aktifChip ? 'bg-white text-accent' : 'bg-white/80 text-neutral-400' }}">
                                <svg class="h-3 w-3" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    {!! $ikon !!}
                                </svg>
                            </span>
                        @endif

                        <span>{{ $label }}</span>
                    </a>
                @endforeach

            </div>

            {{-- Daftar --}}
            <div class="space-y-2">
                @forelse ($transaksi as $item)
                    @php
                        $metode  = $item->pembayaran->first()?->metode;
                        $aktif   = $dipilih && $dipilih->id === $item->id;
                        $dibatal = $item->status === 'batal';
                    @endphp

                    <a href="{{ request()->fullUrlWithQuery(['detail' => $item->id]) }}"
                       class="block rounded-xl border-l-4 px-4 py-1.5 shadow-sm transition
                              {{ $aktif ? 'border-accent bg-cream' : 'border-transparent bg-white hover:bg-cream/60' }}">

                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1">

                            {{-- Baris 1 --}}
                            <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-semibold {{ $dibatal ? 'text-neutral-400 line-through' : '' }}">#{{ $item->no_invoice }}</span>
                                @if ($dibatal)
                                    <span class="rounded-full bg-red-100 px-2 py-px text-[9px] font-semibold text-red-600">Dibatalkan</span>
                                @else
                                    <span class="rounded-full px-2 py-px text-[9px] font-semibold {{ $item->tipe_pesanan === 'takeaway' ? 'bg-accent text-white' : 'bg-neutral-200 text-neutral-700' }}">
                                        {{ $namaTipe[$item->tipe_pesanan] ?? $item->tipe_pesanan }}
                                    </span>
                                @endif
                                <span class="text-[10px] text-neutral-600">Order #{{ $item->nomor_order }}</span>
                            </div>
                            <p class="flex items-center justify-end gap-1 text-[10px] text-neutral-600">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                                </svg>
                                {{ $waktu($item->tanggal_transaksi) }}
                            </p>

                            {{-- Baris 2 --}}
                            <div class="flex min-w-0 flex-wrap items-center gap-1.5 text-[10px]">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                    <circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/>
                                </svg>
                                <span class="truncate font-medium">{{ $item->nama_customer ?: 'Pelanggan' }}</span>
                                @if ($dibatal && $item->alasan_batal)
                                    <span class="rounded-full bg-red-100 px-2 py-px text-[9px] font-medium text-red-600">
                                        {{ \Illuminate\Support\Str::limit($item->alasan_batal, 28) }}
                                    </span>
                                @endif
                                @unless ($kasir)
                                    <span class="text-[10px] text-neutral-400">• Kasir: {{ $item->user?->nama ?? '-' }}</span>
                                @endunless
                            </div>
                            <p class="flex items-center justify-end gap-1 text-[9px] font-semibold {{ $dibatal ? 'text-red-600' : 'text-accent' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dibatal ? 'bg-red-600' : 'bg-accent' }}"></span>
                                {{ $dibatal ? 'Dibatalkan' : ($namaMetode[$metode] ?? '-') }}
                            </p>

                            {{-- Baris 3 --}}
                            <p class="min-w-0 truncate text-[9px] text-neutral-700">{{ $teksItem($item) }}</p>
                            <p class="text-right text-[11px] font-bold {{ $dibatal ? 'text-neutral-400 line-through' : '' }}">Rp {{ $rp($item->total) }}</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl bg-white px-4 py-10 text-center shadow-sm">
                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonNota !!}</svg>
                        <p class="mt-2 text-xs text-neutral-400">Tidak ada transaksi yang cocok.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $transaksi->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: DETAIL STRUK ================= --}}
        <aside class="rounded-2xl bg-white p-4 shadow-sm xl:sticky xl:overflow-y-auto
              {{ $kasir ? 'xl:top-[60px] xl:max-h-[calc(100vh-76px)]' : 'xl:top-[68px] xl:max-h-[calc(100vh-88px)]' }}">
            @if ($dipilih)
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-[12px] font-semibold">Detail Struk</h2>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold">Order #{{ $dipilih->nomor_order }}</p>
                        <p class="text-[9px] text-neutral-500">{{ $dipilih->no_invoice }}</p>
                    </div>
                </div>

                {{-- Isi struk --}}
                <div class="mt-3 font-mono text-[10px] leading-relaxed text-neutral-800">
                    <div class="text-center">
                        <p class="text-[13px] font-bold">{{ $cafe?->nama_cafe ?? config('app.name') }}</p>
                        @if ($cafe?->alamat) <p class="text-neutral-500">{{ $cafe->alamat }}</p> @endif
                        @if ($cafe?->telepon) <p class="text-neutral-500">Telp. {{ $cafe->telepon }}</p> @endif
                    </div>

                    <div class="my-2 border-t border-dashed border-neutral-500"></div>

                    <div class="space-y-0.5">
                        <div class="flex justify-between gap-2"><span>No</span><span>{{ $dipilih->no_invoice }}</span></div>
                        <div class="flex justify-between gap-2"><span>Tanggal</span><span>{{ $dipilih->tanggal_transaksi->format('d/m/Y H:i') }}</span></div>
                        <div class="flex justify-between gap-2"><span>Kasir</span><span>{{ $dipilih->user?->nama ?? '-' }}</span></div>
                        <div class="flex justify-between gap-2"><span>Tipe</span><span>{{ $namaTipe[$dipilih->tipe_pesanan] ?? $dipilih->tipe_pesanan }}</span></div>
                        @if ($dipilih->nama_customer)
                            <div class="flex justify-between gap-2"><span>Customer</span><span>{{ $dipilih->nama_customer }}</span></div>
                        @endif
                    </div>

                    <div class="my-2 border-t border-dashed border-neutral-500"></div>

                    @foreach ($dipilih->detail as $d)
                        <div class="mb-2">
                            <p>{{ $d->menu?->nama ?? 'Menu' }}</p>
                            @if ($d->varian) <p class="text-neutral-500">{{ $d->varian }}</p> @endif
                            @if ($d->catatan) <p class="text-neutral-500">* {{ $d->catatan }}</p> @endif
                            <div class="flex justify-between gap-2">
                                <span>{{ $d->jumlah }} x {{ $rp($d->harga) }}</span>
                                <span>{{ $rp($d->subtotal) }}</span>
                            </div>
                        </div>
                    @endforeach

                    <div class="my-2 border-t border-dashed border-neutral-500"></div>

                    <div class="space-y-0.5">
                        <div class="flex justify-between gap-2"><span>Subtotal</span><span>{{ $rp($dipilih->subtotal) }}</span></div>
                        @if ($dipilih->pajak > 0)
                            <div class="flex justify-between gap-2"><span>Pajak</span><span>{{ $rp($dipilih->pajak) }}</span></div>
                        @endif
                        <div class="flex justify-between gap-2 font-bold"><span>TOTAL</span><span>{{ $rp($dipilih->total) }}</span></div>
                    </div>

                    <div class="my-2 border-t border-dashed border-neutral-500"></div>

                    @if ($pembayaran)
                        <div class="space-y-0.5">
                            <div class="flex justify-between gap-2">
                                <span>Bayar ({{ $namaMetode[$pembayaran->metode] ?? $pembayaran->metode }})</span>
                                <span>{{ $rp($pembayaran->jumlah_bayar) }}</span>
                            </div>
                            <div class="flex justify-between gap-2"><span>Kembalian</span><span>{{ $rp($pembayaran->kembalian) }}</span></div>
                            @if ($pembayaran->no_referensi)
                                <div class="flex justify-between gap-2 text-neutral-500"><span>Ref</span><span>{{ $pembayaran->no_referensi }}</span></div>
                            @endif
                        </div>
                        <div class="my-2 border-t border-dashed border-neutral-500"></div>
                    @endif

                    <div class="text-center">
                        <p>Terima kasih</p>
                        <p>Selamat menikmati</p>
                    </div>
                </div>

                {{-- Info pembatalan --}}
                @if ($dipilih->status === 'batal')
                    <div class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-center text-[11px] text-red-800">
                        <p class="font-semibold">Transaksi dibatalkan</p>
                        <p class="mt-0.5">Alasan: {{ $dipilih->alasan_batal }}</p>
                        <p class="mt-0.5 text-[10px] text-red-800/80">Stok bahan sudah dikembalikan.</p>
                    </div>
                @endif

                {{-- Tombol aksi --}}
                <div class="mt-3 grid grid-cols-1 gap-2">
                    <a href="{{ route($rute['struk'], ['transaksi' => $dipilih, 'cetak' => 1]) }}" target="_blank"
                       class="flex h-9 items-center justify-center gap-1.5 rounded-full bg-accent px-3 text-[10px] font-semibold text-white shadow-sm transition hover:opacity-90">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9V3h12v6M6 18H4a1 1 0 01-1-1v-6a2 2 0 012-2h14a2 2 0 012 2v6a1 1 0 01-1 1h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/>
                        </svg>
                        Cetak Ulang Struk
                    </a>

                    @if ($bisaBatal)
                        <button type="button" @click="batal = true"
                                class="flex h-9 items-center justify-center gap-1.5 rounded-full bg-red-100 px-3 text-[10px] font-semibold text-red-600 transition hover:bg-red-200">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9l6 6M15 9l-6 6"/>
                            </svg>
                            Batalkan Transaksi
                        </button>
                    @endif
                </div>

                @if (! $bisaBatal && $dipilih->status === 'selesai')
                    <p class="mt-2 text-center text-[10px] text-neutral-500">
                        Kasir hanya dapat membatalkan transaksi miliknya pada hari yang sama. Hubungi admin untuk pembatalan lainnya.
                    </p>
                @endif
            @else
                <div class="py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonNota !!}</svg>
                    <p class="mt-2 text-xs text-neutral-400">Pilih transaksi untuk melihat detail struk.</p>
                </div>
            @endif
        </aside>
    </div>

    {{-- ================= POPUP PEMBATALAN ================= --}}
    @if ($dipilih && $bisaBatal)
        <div x-show="batal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <form method="POST" action="{{ route($rute['batal'], $dipilih) }}" @submit="kirim = true"
                  class="w-full max-w-[320px] rounded-2xl bg-white p-4 text-center shadow-xl">
                @csrf
                <h3 class="text-sm font-semibold">Batalkan transaksi</h3>
                <p class="mt-0.5 text-[11px] text-neutral-500">
                    {{ $dipilih->no_invoice }} • Rp {{ $rp($dipilih->total) }}
                </p>

                <p class="mt-2 text-[11px] text-neutral-600">
                    Stok bahan dikembalikan sesuai pemakaian saat penjualan.
                    Pembatalan tidak bisa diurungkan.
                </p>

                <textarea name="alasan" rows="3" required minlength="5" maxlength="255"
                          placeholder="Alasan pembatalan, misalnya: salah input rasa"
                          class="mt-2 w-full resize-none rounded-lg border-0 bg-surface px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">{{ old('alasan') }}</textarea>
                @error('alasan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" @click="batal = false" :disabled="kirim"
                            class="h-9 rounded-lg border border-accent text-[12px] font-semibold text-accent transition hover:bg-accent/5 disabled:opacity-50">
                        Kembali
                    </button>
                    <button :disabled="kirim"
                            class="h-9 rounded-lg bg-red-600 text-[12px] font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                            x-text="kirim ? 'Memproses...' : 'Batalkan Transaksi'"></button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection