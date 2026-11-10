@extends('layouts.admin')

@section('title', 'Barang Masuk')

@section('content')
@php
    $opsiSupplier = $supplier->pluck('nama', 'id')->all();
    $dataBahan    = $bahan->map(fn ($b) => ['id' => (string) $b->id, 'nama' => $b->nama, 'satuan' => $b->satuan])->values();

    $barisAwal = old('item', [['bahan_baku_id' => '', 'jumlah' => '', 'harga_beli' => '']]);
    $barisAwal = collect($barisAwal)->values()->map(fn ($r) => [
        'bahan_baku_id' => (string) ($r['bahan_baku_id'] ?? ''),
        'jumlah'        => $r['jumlah'] ?? '',
        'harga_beli'    => $r['harga_beli'] ?? '',
    ])->all();

    $adaFilter = request('supplier') || request('dari') || request('sampai');

    $ikonMasuk = '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M5 20h14"/>';

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
    $kelasKecil = 'h-9 w-full rounded-lg border-0 bg-white px-2.5 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent';

    $petakan = fn ($b) => [
        'id'      => $b->id,
        'kode'    => $b->kode,
        'tanggal' => $b->tanggal->format('d/m/Y H:i'),
        'supplier'=> $b->supplier->nama,
        'petugas' => $b->user->nama,
        'catatan' => $b->catatan,
        'total'   => 'Rp ' . number_format($b->total, 0, ',', '.'),
        'detail'  => $b->detail->map(fn ($d) => [
            'nama'     => $d->bahanBaku->nama,
            'jumlah'   => \App\Models\BahanBaku::angka($d->jumlah) . ' ' . $d->bahanBaku->satuan,
            'harga'    => 'Rp ' . number_format($d->harga_beli, 0, ',', '.'),
            'subtotal' => 'Rp ' . number_format($d->subtotal, 0, ',', '.'),
        ])->values()->all(),
    ];

    $daftarBM = $barangMasuk->getCollection()->map($petakan)->values();
    $bukaId     = $bukaId ?? null;
    $barangBuka = $barangBuka ?? null;

    $daftarBM = $barangMasuk->getCollection()->map($petakan)->values();
    if ($barangBuka && ! $daftarBM->contains(fn ($x) => $x['id'] === $barangBuka->id)) {
        $daftarBM->push($petakan($barangBuka));
    }
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="detailBM(@js($daftarBM), @js($bukaId))"
     @keydown.escape.window="tutup()">
    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_380px]">

        {{-- ================= KIRI: DAFTAR ================= --}}
        <section class="min-w-0">

            {{-- Filter --}}
            <form method="GET" action="{{ route('barang-masuk.index') }}" x-data
                  class="mb-3 flex flex-wrap items-end gap-3 rounded-xl bg-white p-3 shadow-sm">

                <div class="w-[200px]">
                    <label class="text-[10px] font-medium text-neutral-500">Supplier</label>
                    <x-pilihan name="supplier" :options="$opsiSupplier" :selected="request('supplier')" semua="Semua supplier" />
                </div>

                <div class="w-[150px]">
                    <label class="text-[10px] font-medium text-neutral-500">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ request('dari') }}" @change="$el.form.submit()"
                           class="mt-1.5 h-9 w-full rounded-full border-0 bg-surface px-4 text-[11px] text-neutral-700 focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                <div class="w-[150px]">
                    <label class="text-[10px] font-medium text-neutral-500">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" @change="$el.form.submit()"
                           class="mt-1.5 h-9 w-full rounded-full border-0 bg-surface px-4 text-[11px] text-neutral-700 focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                @if ($adaFilter)
                    <a href="{{ route('barang-masuk.index') }}"
                       class="flex h-9 items-center rounded-full border border-accent px-4 text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Kode</th>
                                <th class="px-4 py-2.5 font-semibold">Tanggal</th>
                                <th class="px-4 py-2.5 font-semibold">Supplier</th>
                                <th class="px-4 py-2.5 font-semibold">Petugas</th>
                                <th class="px-4 py-2.5 font-semibold">Bahan</th>
                                <th class="px-4 py-2.5 font-semibold">Total</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($barangMasuk as $item)
                                <tr class="border-t border-neutral-100 transition hover:bg-cream/40">
                                    <td class="whitespace-nowrap px-4 py-2 text-[12px] font-semibold">{{ $item->kode }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">
                                        <p class="font-semibold">{{ $item->tanggal->format('d/m/Y') }}</p>
                                        <p class="text-[10px] text-neutral-400">{{ $item->tanggal->format('H:i') }}</p>
                                    </td>
                                    <td class="px-4 py-2">{{ $item->supplier->nama }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $item->user->nama }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">
                                        <span class="inline-block whitespace-nowrap rounded-full bg-cream px-2.5 py-0.5 text-[10px] font-medium text-accent">
                                            {{ $item->detail->count() }} bahan
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 font-semibold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex justify-end">
                                            <a href="{{ route('barang-masuk.show', $item) }}" @click.prevent="buka({{ $item->id }})"
                                                title="Lihat detail" aria-label="Lihat detail {{ $item->kode }}"
                                                class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonMasuk !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada barang masuk yang cocok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $barangMasuk->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: CATAT BARANG MASUK ================= --}}
        <aside class="rounded-2xl bg-white p-3 shadow-sm">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonMasuk !!}</svg>
                </span>
                <div>
                    <h2 class="text-[13px] font-semibold leading-tight">Catat Barang Masuk</h2>
                    <p class="text-[10px] text-neutral-500">Stok bertambah setelah disimpan.</p>
                </div>
            </div>

            @if ($supplier->isEmpty() || $bahan->isEmpty())
                <div class="mt-3 rounded-xl bg-amber-50 px-3 py-3 text-[11px] text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">Data belum lengkap.</p>
                    <ul class="mt-1 list-inside list-disc text-[10px]">
                        @if ($supplier->isEmpty()) <li>Belum ada supplier. Minta admin menambahkannya di Data Supplier.</li> @endif
                        @if ($bahan->isEmpty()) <li>Belum ada bahan baku. Minta admin menambahkannya di Data Bahan Baku.</li> @endif
                    </ul>
                </div>
            @else
                <form method="POST" action="{{ route('barang-masuk.store') }}"
                      x-data="barangMasukForm(@js($barisAwal), @js($dataBahan))"
                      @submit="kirim = true"
                      class="mt-2 space-y-2 border-t border-neutral-200 pt-2">
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-lg bg-red-100 px-3 py-2 text-[10px] text-red-800">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $pesan) <li>{{ $pesan }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Supplier</label>
                        <x-pilihan name="supplier_id"
                                   :options="$opsiSupplier"
                                   :selected="old('supplier_id')"
                                   :semua="null"
                                   placeholder="Pilih supplier"
                                   :auto="false" />
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Tanggal dan waktu</label>
                        <input type="datetime-local" name="tanggal" required
                               value="{{ old('tanggal', now()->format('Y-m-d\TH:i')) }}" class="{{ $kelasInput }}">
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Catatan <span class="font-normal text-neutral-400">(opsional)</span></label>
                        <input type="text" name="catatan" value="{{ old('catatan') }}" maxlength="500"
                               placeholder="Contoh: nomor surat jalan" class="{{ $kelasInput }}">
                    </div>

                    {{-- Bahan yang diterima --}}
                    <div>
                        <p class="text-[11px] font-medium text-neutral-700">Bahan yang diterima</p>

                        <div class="mt-1 space-y-1.5">
                            <template x-for="(row, i) in rows" :key="i">
                                <div class="space-y-1.5 rounded-lg bg-cream/50 p-2">

                                    {{-- Dropdown bahan (Alpine.js) + hapus --}}
                                    <div class="flex items-center gap-1.5">
                                        <div class="relative min-w-0 flex-1" @click.outside="if (dd === i) dd = null">
                                            <input type="hidden" :name="`item[${i}][bahan_baku_id]`" :value="row.bahan_baku_id">

                                            <button type="button" @click="dd = (dd === i) ? null : i"
                                                    :class="dd === i ? 'ring-2 ring-accent' : ''"
                                                    class="flex h-9 w-full items-center justify-between gap-1 rounded-lg bg-white px-2.5 text-left text-[11px] transition">
                                                <span class="truncate" :class="row.bahan_baku_id ? '' : 'text-neutral-400'"
                                                      x-text="namaBahan(row.bahan_baku_id) || 'Pilih bahan'"></span>
                                                <svg class="h-3.5 w-3.5 shrink-0 text-neutral-500 transition-transform" :class="dd === i ? 'rotate-180' : ''"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>

                                            <div x-show="dd === i" x-cloak x-transition.origin.top
                                                 class="absolute left-0 top-full z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">
                                                <template x-for="b in bahan" :key="b.id">
                                                    <button type="button"
                                                            @click="if (!dipakai(b.id, i)) { row.bahan_baku_id = b.id; dd = null }"
                                                            :disabled="dipakai(b.id, i)"
                                                            :class="row.bahan_baku_id === b.id
                                                                ? 'bg-cream font-semibold text-accent'
                                                                : (dipakai(b.id, i) ? 'cursor-not-allowed text-neutral-300' : 'text-neutral-700 hover:bg-cream/60')"
                                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-[11px] transition">
                                                        <span class="truncate" x-text="b.nama"></span>
                                                        <svg x-show="row.bahan_baku_id === b.id" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none"
                                                             stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <button type="button" @click="hapus(i)" title="Hapus bahan" aria-label="Hapus bahan"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Jumlah dan harga --}}
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div class="relative">
                                            <input type="number" min="0" step="0.01" x-model="row.jumlah" required placeholder="Jumlah"
                                                   :name="`item[${i}][jumlah]`"
                                                   class="{{ $kelasKecil }} pr-9">
                                            <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-neutral-500"
                                                  x-text="satuan(row.bahan_baku_id)"></span>
                                        </div>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] text-neutral-400">Rp</span>
                                            <input type="number" min="0" step="1" x-model="row.harga_beli" required placeholder="Harga/satuan"
                                                   :name="`item[${i}][harga_beli]`"
                                                   class="{{ $kelasKecil }} pl-7">
                                        </div>
                                    </div>

                                    <p class="text-right text-[10px] text-neutral-500">
                                        Subtotal <span class="font-semibold text-neutral-800" x-text="rupiah(subtotal(row))"></span>
                                    </p>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="tambah()"
                                class="mt-1.5 text-[11px] font-medium text-accent hover:underline">+ Tambah bahan</button>
                    </div>

                    {{-- Total --}}
                    <div class="flex items-center justify-between rounded-lg bg-cream px-3 py-2">
                        <span class="text-[11px] text-neutral-600">Total pembelian</span>
                        <span class="text-[13px] font-semibold" x-text="rupiah(total)"></span>
                    </div>

                    <button type="submit" :disabled="kirim"
                            class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:opacity-50">
                        Simpan Barang Masuk
                    </button>
                </form>
            @endif
        </aside>
    </div>
    {{-- ================= POPUP DETAIL ================= --}}
<div x-show="id !== null" x-cloak @click.self="tutup()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

    <template x-if="m">
        <div class="max-h-[90vh] w-full max-w-[520px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">

            <div class="flex items-center justify-between gap-2 border-b border-neutral-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonMasuk !!}</svg>
                    </span>
                    <div>
                        <h3 class="text-[13px] font-semibold leading-tight" x-text="'Barang Masuk ' + m.kode"></h3>
                        <p class="text-[10px] text-neutral-500" x-text="m.tanggal"></p>
                    </div>
                </div>
                <button type="button" @click="tutup()" aria-label="Tutup"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <dl class="mt-3 grid grid-cols-2 gap-3 text-[11px]">
                <div><dt class="text-[10px] text-neutral-500">Supplier</dt><dd class="font-semibold" x-text="m.supplier"></dd></div>
                <div><dt class="text-[10px] text-neutral-500">Petugas</dt><dd class="font-semibold" x-text="m.petugas"></dd></div>
                <div class="col-span-2"><dt class="text-[10px] text-neutral-500">Catatan</dt><dd class="font-semibold" x-text="m.catatan || '-'"></dd></div>
            </dl>

            <div class="mt-3 overflow-hidden rounded-xl border border-neutral-200">
                <table class="w-full text-[11px]">
                    <thead class="bg-cream text-left text-accent">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Bahan</th>
                            <th class="px-3 py-2 font-semibold">Jumlah</th>
                            <th class="px-3 py-2 font-semibold">Harga beli</th>
                            <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="d in m.detail" :key="d.nama">
                            <tr class="border-t border-neutral-100">
                                <td class="px-3 py-2 font-semibold" x-text="d.nama"></td>
                                <td class="whitespace-nowrap px-3 py-2" x-text="d.jumlah"></td>
                                <td class="whitespace-nowrap px-3 py-2" x-text="d.harga"></td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold" x-text="d.subtotal"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex items-center justify-between rounded-lg bg-cream px-3 py-2">
                <span class="text-[11px] text-neutral-600">Total pembelian</span>
                <span class="text-[13px] font-semibold" x-text="m.total"></span>
            </div>

            <p class="mt-3 text-[10px] leading-snug text-neutral-500">
                Barang masuk tidak bisa diubah atau dihapus. Jika ada kesalahan, ajukan koreksi lewat Penyesuaian Stok.
            </p>

            <button type="button" @click="tutup()"
                    class="mt-3 h-9 w-full rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                Tutup
            </button>
        </div>
    </template>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('barangMasukForm', (baris, bahan) => ({
        rows: baris,
        bahan,
        dd: null,       // indeks baris yang dropdown-nya terbuka
        kirim: false,   // mencegah tombol simpan ditekan dua kali

        tambah() { this.rows.push({ bahan_baku_id: '', jumlah: '', harga_beli: '' }); },
        hapus(i) {
            this.dd = null;
            this.rows.splice(i, 1);
            if (this.rows.length === 0) this.tambah();
        },
        namaBahan(id) {
            const b = this.bahan.find(b => String(b.id) === String(id));
            return b ? b.nama : '';
        },
        satuan(id) {
            const b = this.bahan.find(b => String(b.id) === String(id));
            return b ? b.satuan : '';
        },
        // Bahan yang sudah dipilih di baris lain
        dipakai(bid, indeks) {
            return this.rows.some((r, i) => i !== indeks && String(r.bahan_baku_id) === String(bid));
        },
        subtotal(row) { return (parseFloat(row.jumlah) || 0) * (parseFloat(row.harga_beli) || 0); },
        get total() { return this.rows.reduce((t, r) => t + this.subtotal(r), 0); },
        rupiah(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); },
    }));

    Alpine.data('detailBM', (daftar, bukaId) => ({
        daftar,
        id: null,
        get m() { return this.daftar.find(x => x.id === this.id) || null; },
        buka(id) { this.id = id; },
        tutup() { this.id = null; },
        init() { if (bukaId) this.id = bukaId; },
    }));
});
</script>
@endpush