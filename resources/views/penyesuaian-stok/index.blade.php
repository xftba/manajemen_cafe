@extends('layouts.admin')

@section('title', 'Penyesuaian Stok')

@section('content')
@php
    $admin = auth()->user()->role === 'admin';
    $angka = fn ($n) => \App\Models\BahanBaku::angka($n);

    $warna = [
        'pending'   => 'bg-amber-100 text-amber-800',
        'disetujui' => 'bg-green-100 text-green-800',
        'ditolak'   => 'bg-red-100 text-red-700',
    ];
    $titik = [
        'pending'   => 'bg-amber-600',
        'disetujui' => 'bg-green-600',
        'ditolak'   => 'bg-red-600',
    ];
    $label = ['pending' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];

    $opsiBahan  = $bahan->pluck('nama', 'id')->all();
    $opsiStatus = $label;

    $adaFilter = filled(request('bahan')) || filled(request('status'));

    $ikonMenunggu  = '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>';
    $ikonDisetujui = '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>';
    $ikonDitolak   = '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>';
    $ikonKosong    = '<path d="M9 4h6v2H9z"/><rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 13l2 2 4-4"/>';

    $kartu = [
        ['Menunggu',  \App\Models\PenyesuaianStok::where('status', 'pending')->count(),   $ikonMenunggu,  'bg-amber-100 text-amber-700', 'text-amber-700'],
        ['Disetujui', \App\Models\PenyesuaianStok::where('status', 'disetujui')->count(), $ikonDisetujui, 'bg-green-100 text-green-700', 'text-green-700'],
        ['Ditolak',   \App\Models\PenyesuaianStok::where('status', 'ditolak')->count(),   $ikonDitolak,   'bg-red-100 text-red-600',     'text-red-600'],
    ];

    $kelasPopup = 'mt-1.5 w-full resize-none rounded-lg border-0 bg-surface px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent';
    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';

    // Data panel ajukan (staff gudang)
    $dataBahan = $bahan->map(fn ($b) => [
        'id'     => (string) $b->id,
        'nama'   => $b->nama,
        'satuan' => $b->satuan,
        'stok'   => (float) $b->stok,
    ])->values();
    $pending = collect($pending ?? [])->map(fn ($id) => (string) $id)->values();
@endphp

<div class="mx-auto max-w-[1400px]" x-data="{ tolak: null }" @keydown.escape.window="tolak = null">

    @error('catatan_admin')
        <div class="mb-3 rounded-lg bg-red-100 px-3 py-2 text-xs text-red-800">{{ $message }}</div>
    @enderror

    <div class="grid items-start gap-3 {{ $admin ? '' : 'xl:grid-cols-[minmax(0,1fr)_320px]' }}">

        {{-- ================= KIRI: DAFTAR ================= --}}
        <section class="min-w-0">

            {{-- Ringkasan --}}
            <div class="mb-3 grid grid-cols-3 gap-3">
                @foreach ($kartu as [$judul, $nilai, $ikon, $warnaIkon, $warnaAngka])
                    <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] text-neutral-500">{{ $judul }}</p>
                            <p class="text-xl font-semibold leading-tight {{ $warnaAngka }}">{{ $nilai }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Filter --}}
            <form method="GET" action="{{ route('penyesuaian-stok.index') }}" x-data
                  class="mb-3 flex flex-wrap items-start gap-x-4 gap-y-3 rounded-xl bg-white p-3 shadow-sm">

                <div>
                    <p class="mb-1 text-[10px] font-medium text-neutral-500">Bahan baku</p>
                    <x-pilihan name="bahan" :options="$opsiBahan" :selected="request('bahan')" semua="Semua bahan" />
                </div>

                <div>
                    <p class="mb-1 text-[10px] font-medium text-neutral-500">Status</p>
                    <x-pilihan name="status" :options="$opsiStatus" :selected="request('status')" semua="Semua status" />
                </div>

                @if ($adaFilter)
                    <div class="self-end">
                        <a href="{{ route('penyesuaian-stok.index') }}"
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
                                <th class="px-4 py-2.5 font-semibold">Bahan</th>
                                <th class="px-4 py-2.5 font-semibold">Stok</th>
                                <th class="px-4 py-2.5 font-semibold">Selisih</th>
                                <th class="px-4 py-2.5 font-semibold">Alasan</th>
                                <th class="px-4 py-2.5 font-semibold">Diajukan</th>
                                <th class="px-4 py-2.5 font-semibold">Status</th>
                                <th class="px-4 py-2.5 font-semibold">Diproses</th>
                                @if ($admin) <th class="px-4 py-2.5 text-right font-semibold">Aksi</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penyesuaian as $p)
                                @php $selisih = $p->selisih; @endphp
                                <tr class="border-t border-neutral-100 align-top transition hover:bg-cream/40">

                                    <td class="whitespace-nowrap px-4 py-2">
                                        <p class="text-[12px] font-semibold">{{ $p->bahanBaku->nama }}</p>
                                        <p class="text-[10px] text-neutral-400">{{ $p->created_at->format('d/m/Y H:i') }}</p>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2 text-neutral-500">
                                        {{ $angka($p->stok_sistem) }} <span class="text-neutral-300">→</span>
                                        <span class="font-semibold text-neutral-800">{{ $angka($p->stok_fisik) }}</span>
                                        <span class="text-[10px] text-neutral-400">{{ $p->bahanBaku->satuan }}</span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2 font-semibold {{ $selisih >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $selisih >= 0 ? '+' : '−' }}{{ $angka(abs($selisih)) }} {{ $p->bahanBaku->satuan }}
                                    </td>

                                    <td class="max-w-[220px] px-4 py-2 text-neutral-700">{{ $p->alasan }}</td>

                                    <td class="whitespace-nowrap px-4 py-2">{{ $p->pengaju->nama }}</td>

                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $warna[$p->status] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $titik[$p->status] }}"></span>
                                            {{ $label[$p->status] }}
                                        </span>
                                    </td>

                                    <td class="max-w-[200px] px-4 py-2 text-neutral-500">
                                        @if ($p->status === 'pending')
                                            <span class="text-neutral-300">-</span>
                                        @else
                                            <p class="font-medium text-neutral-700">{{ $p->penyetuju?->nama }}</p>
                                            <p class="text-[10px] text-neutral-400">{{ $p->updated_at->format('d/m/Y H:i') }}</p>
                                            @if ($p->catatan_admin)
                                                <p class="mt-0.5 text-[10px] text-red-600">{{ $p->catatan_admin }}</p>
                                            @endif
                                        @endif
                                    </td>

                                    @if ($admin)
                                        <td class="px-4 py-2">
                                            @if ($p->status === 'pending')
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <form method="POST" action="{{ route('penyesuaian-stok.setujui', $p) }}"
                                                          onsubmit="return confirm(@js('Setujui? Stok ' . $p->bahanBaku->nama . ' akan berubah ' . ($selisih >= 0 ? '+' : '-') . $angka(abs($selisih)) . ' ' . $p->bahanBaku->satuan . '.'))">
                                                        @csrf
                                                        <button type="submit" title="Setujui" aria-label="Setujui pengajuan {{ $p->bahanBaku->nama }}"
                                                                class="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-green-700 transition hover:bg-green-200">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                            @click="tolak = { url: @js(route('penyesuaian-stok.tolak', $p)), nama: @js($p->bahanBaku->nama) }"
                                                            title="Tolak" aria-label="Tolak pengajuan {{ $p->bahanBaku->nama }}"
                                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                             stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @else
                                                <p class="text-right text-neutral-300">-</p>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $admin ? 8 : 7 }}" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonKosong !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Belum ada pengajuan penyesuaian stok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $penyesuaian->withQueryString()->links('vendor.pagination.custom') }}</div>
        </section>

        {{-- ================= KANAN: AJUKAN PENYESUAIAN (staff gudang) ================= --}}
        @unless ($admin)
        <aside class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px]">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonKosong !!}</svg>
                </span>
                <div>
                    <h2 class="text-[13px] font-semibold leading-tight">Ajukan Penyesuaian</h2>
                    <p class="text-[10px] text-neutral-500">Diperiksa admin sebelum stok berubah.</p>
                </div>
            </div>

            @if ($bahan->isEmpty())
                <div class="mt-3 rounded-xl bg-amber-50 px-3 py-3 text-[11px] text-amber-900 ring-1 ring-amber-200">
                    Belum ada bahan baku. Minta admin menambahkannya di Data Bahan Baku.
                </div>
            @else
                <form method="POST" action="{{ route('penyesuaian-stok.store') }}"
                      x-data="penyesuaianForm(@js($dataBahan), @js($pending), @js(old('bahan_baku_id', '')), @js(old('stok_fisik', '')))"
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

                    <p class="text-[10px] leading-snug text-neutral-500">
                        Hitung stok fisik di gudang, lalu isi hasilnya.
                    </p>

                    {{-- Bahan (dropdown Alpine.js) --}}
                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Bahan baku</label>
                        <div class="relative" @click.outside="buka = false">
                            <input type="hidden" name="bahan_baku_id" :value="bahanId">
                            <button type="button" @click="buka = !buka"
                                    :class="buka ? 'ring-2 ring-accent' : ''"
                                    class="mt-1 flex h-9 w-full items-center justify-between gap-1 rounded-lg bg-surface px-3 text-left text-[11px] transition">
                                <span class="truncate" :class="terpilih ? '' : 'text-neutral-400'"
                                      x-text="terpilih ? terpilih.nama : 'Pilih bahan'"></span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-neutral-500 transition-transform" :class="buka ? 'rotate-180' : ''"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                            </button>

                            <div x-show="buka" x-cloak x-transition.origin.top
                                 class="absolute left-0 top-full z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">
                                <template x-for="b in bahan" :key="b.id">
                                    <button type="button"
                                            @click="if (!menunggu(b.id)) { bahanId = b.id; buka = false }"
                                            :disabled="menunggu(b.id)"
                                            :class="b.id === bahanId
                                                ? 'bg-cream font-semibold text-accent'
                                                : (menunggu(b.id) ? 'cursor-not-allowed text-neutral-300' : 'text-neutral-700 hover:bg-cream/60')"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-[11px] transition">
                                        <span class="truncate" x-text="b.nama"></span>
                                        <span x-show="menunggu(b.id)" class="shrink-0 text-[9px] font-normal">menunggu</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[11px] font-medium text-neutral-700">Stok sistem</label>
                            <div class="mt-1 flex h-9 items-center rounded-lg bg-surface px-3 text-[11px] font-semibold text-neutral-700"
                                 x-text="terpilih ? angka(terpilih.stok) + ' ' + terpilih.satuan : '-'"></div>
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-neutral-700">Stok fisik</label>
                            <input type="number" name="stok_fisik" min="0" step="0.01" x-model="fisik" required
                                   placeholder="0" class="{{ $kelasInput }}">
                        </div>
                    </div>

                    <div x-show="terpilih && fisik !== ''" x-cloak
                         class="flex items-center justify-between rounded-lg bg-cream px-3 py-2">
                        <span class="text-[11px] text-neutral-600">Selisih</span>
                        <span class="text-[12px] font-semibold" :class="selisih >= 0 ? 'text-green-700' : 'text-red-600'"
                              x-text="terpilih ? (selisih >= 0 ? '+' : '−') + angka(Math.abs(selisih)) + ' ' + terpilih.satuan : ''"></span>
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-neutral-700">Alasan</label>
                        <textarea name="alasan" rows="3" required minlength="5" maxlength="255"
                                  placeholder="Contoh: hasil stock opname bulanan, ada tumpahan"
                                  class="mt-1 w-full resize-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">{{ old('alasan') }}</textarea>
                    </div>

                    <button type="submit" :disabled="kirim || !bahanId"
                            class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50">
                        Kirim Pengajuan
                    </button>
                </form>
            @endif
        </aside>
        @endunless
    </div>

    {{-- ================= POPUP PENOLAKAN ================= --}}
    @if ($admin)
        <div x-show="tolak" x-cloak @click.self="tolak = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

            <form method="POST" :action="tolak ? tolak.url : ''"
                  class="w-full max-w-[340px] rounded-2xl bg-white p-4 shadow-xl">
                @csrf

                <div class="flex items-center gap-3 border-b border-neutral-200 pb-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonDitolak !!}</svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold">Tolak pengajuan</h3>
                        <p class="truncate text-[11px] text-neutral-500" x-text="tolak ? tolak.nama : ''"></p>
                    </div>
                </div>

                <label class="mt-3 block text-[11px] font-semibold">Alasan penolakan</label>
                <textarea name="catatan_admin" rows="3" required minlength="5" maxlength="255"
                          placeholder="Tulis alasan penolakan"
                          class="{{ $kelasPopup }}"></textarea>
                <p class="mt-1 text-[10px] text-neutral-500">Stok tidak berubah. Alasan ini terlihat oleh staff gudang.</p>

                <div class="mt-3 grid grid-cols-[80px_1fr] gap-2">
                    <button type="button" @click="tolak = null"
                            class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                        Batal
                    </button>
                    <button type="submit"
                            class="h-9 rounded-lg bg-red-600 text-[11px] font-semibold text-white transition hover:bg-red-700">
                        Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('penyesuaianForm', (bahan, pending, bahanAwal, fisikAwal) => ({
        bahan,
        pending,
        bahanId: String(bahanAwal || ''),
        fisik: fisikAwal,
        buka: false,
        kirim: false,   // mencegah tombol kirim ditekan dua kali

        menunggu(id) { return this.pending.includes(String(id)); },
        get terpilih() { return this.bahan.find(b => b.id === this.bahanId) || null; },
        get selisih() { return (parseFloat(this.fisik) || 0) - (this.terpilih ? this.terpilih.stok : 0); },
        angka(n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
    }));
});
</script>
@endpush