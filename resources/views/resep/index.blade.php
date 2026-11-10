@extends('layouts.admin')

@section('title', 'Resep Menu')

@section('content')
@php
    $ukuran = config('cafe.ukuran');   // kode => label
    $editId = old('_edit_id');

    $daftarResep = $menu->getCollection()->map(function ($item) use ($ukuran) {
        $perUkuran = $item->resep->groupBy('ukuran');
        $kode      = $item->pakai_varian ? array_keys($ukuran) : ['small'];

        $data = [];
        foreach (array_keys($ukuran) as $u) {
            $data[$u] = ($perUkuran->get($u) ?? collect())
                ->map(fn ($r) => [
                    'bahan_baku_id' => (string) $r->bahan_baku_id,
                    'takaran'       => (float) $r->takaran,
                    'temperatur'    => $r->temperatur ?? '',
                    'ikut_gula'     => (bool) $r->ikut_gula,
                ])
                ->values()->all();
        }

        return [
            'id'        => $item->id,
            'nama'      => $item->nama,
            'kategori'  => $item->kategori->nama,
            'varian'    => (bool) $item->pakai_varian,
            'ukuran'    => collect($kode)->map(fn ($u) => ['kode' => $u, 'label' => $item->pakai_varian ? $ukuran[$u] : 'Resep'])->values()->all(),
            'data'      => $data,
            'urlUpdate' => route('resep.update', $item),
        ];
    })->values();

    $lamaResep = $editId ? old('resep') : null;

    $dataBahan = $bahan->map(fn ($b) => ['id' => (string) $b->id, 'nama' => $b->nama, 'satuan' => $b->satuan])->values();

    $ikonResep = '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v2H9zM9 11h6M9 15h4"/>';
    $ikonOk    = '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>';
    $ikonBelum = '<circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/>';

    $totalMenu = \App\Models\Menu::count();
    $sudahAda  = \App\Models\Menu::has('resep')->count();

    $kartu = [
        ['Total menu',      $totalMenu,             $ikonResep, 'bg-cream text-accent',            'text-neutral-900'],
        ['Sudah ada resep', $sudahAda,              $ikonOk,    'bg-green-100 text-green-700',     'text-green-700'],
        ['Belum ada resep', $totalMenu - $sudahAda, $ikonBelum, 'bg-neutral-200 text-neutral-500', 'text-neutral-500'],
    ];
@endphp

<div class="mx-auto max-w-[1400px]"
     x-data="editResep(@js($daftarResep), @js($dataBahan), @js($editId ? (int) $editId : null), @js($lamaResep))"
     @keydown.escape.window="tutup()">

    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_380px]">

        {{-- ================= KIRI: DAFTAR RESEP ================= --}}
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

            {{-- Pencarian dan filter --}}
            <form method="GET" action="{{ route('resep.index') }}" x-data
                  class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari nama menu..." autocomplete="off"
                           x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                           @input.debounce.500ms="$el.form.submit()"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                <x-pilihan name="status"
                           :options="['sudah' => 'Sudah ada resep', 'belum' => 'Belum ada resep']"
                           :selected="request('status')"
                           semua="Semua menu" />
            </form>

            {{-- Kartu menu --}}
            @if ($menu->isEmpty())
                <div class="rounded-xl bg-white px-4 py-12 text-center shadow-sm">
                    <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonResep !!}</svg>
                    <p class="mt-2 text-xs text-neutral-400">Belum ada menu yang cocok. Buat menu di Manajemen Menu dulu.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($menu as $item)
                        @php
                            $perUkuran = $item->resep->groupBy('ukuran');
                            $daftar    = $item->pakai_varian ? array_keys($ukuran) : ['small'];
                            $adaResep  = $item->resep->isNotEmpty();
                        @endphp
                        <div class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-2 ring-transparent transition hover:shadow-md"
                             :class="id === {{ $item->id }} ? '!ring-accent' : ''">

                            <div class="relative w-full overflow-hidden bg-gradient-to-br from-cream to-surface">
                                @if ($item->gambar)
                                    <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->nama }}" loading="lazy"
                                         class="h-full w-full object-cover object-center transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-3xl font-semibold text-accent/40">
                                        {{ strtoupper(substr($item->nama, 0, 1)) }}
                                    </span>
                                @endif

                                <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[9px] font-semibold text-accent shadow-sm backdrop-blur">
                                    {{ $item->kategori->nama }}
                                </span>
                            </div>

                            <div class="flex flex-1 flex-col p-2.5">
                                <p class="truncate text-[12px] font-semibold" title="{{ $item->nama }}">{{ $item->nama }}</p>

                                {{-- Status bahan per ukuran --}}
                                <div class="mt-1.5 flex gap-1">
                                    @foreach ($daftar as $u)
                                        @php $n = $perUkuran->get($u)?->count() ?? 0; @endphp
                                        <span class="flex flex-1 items-center justify-center gap-1 rounded-md px-1 py-0.5 text-[9px] font-semibold
                                                     {{ $n ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700' }}"
                                              title="{{ $item->pakai_varian ? $ukuran[$u] : 'Resep' }}: {{ $n ? $n . ' bahan' : 'belum ada' }}">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $n ? 'bg-green-600' : 'bg-red-500' }}"></span>
                                            @if ($item->pakai_varian)
                                                {{ mb_substr($ukuran[$u], 0, 1) }}<span class="font-normal opacity-80">{{ $n ?: '–' }}</span>
                                            @else
                                                {{ $n ? $n . ' bahan' : 'Belum ada resep' }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Aksi --}}
                                <a href="{{ route('resep.edit', $item) }}" @click.prevent="buka({{ $item->id }})"
                                   aria-label="{{ $adaResep ? 'Ubah' : 'Buat' }} resep {{ $item->nama }}"
                                   class="mt-2.5 flex h-8 items-center justify-center gap-1.5 rounded-lg text-[10px] font-semibold transition
                                          {{ $adaResep ? 'border border-accent text-accent hover:bg-accent/5' : 'bg-accent text-white hover:opacity-90' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        @if ($adaResep)
                                            <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                        @else
                                            <path d="M12 5v14M5 12h14"/>
                                        @endif
                                    </svg>
                                    {{ $adaResep ? 'Ubah resep' : 'Buat resep' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <<div class="mt-4 border-t border-neutral-100 pt-4">
                {{ $menu->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </section>

        {{-- ================= KANAN: UBAH RESEP ================= --}}
        <aside x-ref="panel" class="rounded-2xl bg-white p-3 shadow-sm xl:sticky xl:top-[60px] xl:max-h-[calc(100vh-76px)] xl:overflow-y-auto">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikonResep !!}</svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-[13px] font-semibold leading-tight"
                        x-text="m ? 'Resep: ' + m.nama : 'Ubah Resep'"></h2>
                    <p class="text-[10px] text-neutral-500"
                       x-text="m ? m.kategori : 'Pilih menu dari daftar.'"></p>
                </div>
            </div>

            {{-- Kosong --}}
            <div x-show="!m" class="mt-3 rounded-xl bg-cream/60 px-4 py-8 text-center">
                <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonResep !!}</svg>
                <p class="mt-2 text-[11px] text-neutral-500">
                    Tekan tombol ubah resep pada salah satu menu untuk membuat atau mengubah resepnya.
                </p>
            </div>

            <template x-if="m">
                <form method="POST" :action="m.urlUpdate" @submit.prevent="kirim($event)"
                      class="mt-2 border-t border-neutral-200 pt-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_edit_id" :value="m.id">

                    <p class="text-[10px] leading-snug text-neutral-500">
                        Takaran bahan untuk <strong>satu porsi</strong><span x-show="m.ukuran.length > 1"> pada ukuran yang dipilih</span>.
                        Dipakai untuk mengurangi stok otomatis saat ada penjualan.
                        <span x-show="m.varian">Bahan seperti es batu bisa ditandai <strong>Ice</strong> saja.</span>
                    </p>

                    @if ($errors->any() && $editId)
                        <div x-show="id === bukaId" class="mt-2 rounded-lg bg-red-100 px-3 py-2 text-[10px] text-red-800">
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $pesan) <li>{{ $pesan }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Tab ukuran --}}
                    <div x-show="m.ukuran.length > 1" class="mt-2 flex gap-1 border-b border-neutral-200">
                        <template x-for="u in m.ukuran" :key="u.kode">
                            <button type="button" @click="tab = u.kode; dd = null"
                                    :class="tab === u.kode ? 'border-accent text-neutral-900' : 'border-transparent text-neutral-400 hover:text-neutral-700'"
                                    class="-mb-px flex items-center gap-1.5 border-b-2 px-2.5 py-1.5 text-[11px] font-semibold transition">
                                <span x-text="u.label"></span>
                                <span class="rounded-full px-1.5 py-px text-[9px] font-semibold"
                                      :class="jumlah(u.kode) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'"
                                      x-text="jumlah(u.kode)"></span>
                            </button>
                        </template>
                    </div>

                    <template x-for="u in m.ukuran" :key="u.kode">
                        <div x-show="tab === u.kode" class="mt-2 space-y-2">

                            {{-- Salin dari Small --}}
                            <div x-show="u.kode !== 'small'" class="flex flex-wrap items-center gap-2 rounded-xl bg-cream/60 px-3 py-2 text-[10px]">
                                <span>Salin dari Small, faktor</span>
                                <input type="number" min="0.1" max="10" step="0.05" x-model="faktor"
                                       class="h-8 w-16 rounded-lg border-0 bg-white px-2 text-[11px] focus:outline-none focus:ring-2 focus:ring-accent">
                                <button type="button" @click="salin(u.kode)"
                                        class="h-8 rounded-full bg-accent px-3 text-[10px] font-semibold text-white transition hover:opacity-90">Salin</button>
                            </div>

                            <p x-show="u.kode !== 'small' && data[u.kode].length === 0" class="text-[10px] text-neutral-500">
                                Belum ada resep untuk ukuran ini. Ukuran ini <strong>tidak bisa dijual</strong> di Pos Kasir sampai resepnya dibuat.
                            </p>

                            <template x-for="(row, i) in data[u.kode]" :key="i">
                                <div class="rounded-xl bg-cream/40 p-1.5">
                                    <div class="grid grid-cols-[minmax(0,1fr)_92px_28px] items-center gap-1.5">

                                        {{-- Dropdown bahan --}}
                                        <div class="relative min-w-0" @click.outside="if (dd === u.kode + '-' + i) dd = null">
                                            <input type="hidden" :name="`resep[${u.kode}][${i}][bahan_baku_id]`" :value="row.bahan_baku_id">

                                            <button type="button" @click="dd = (dd === u.kode + '-' + i) ? null : u.kode + '-' + i"
                                                    :class="dd === u.kode + '-' + i ? 'ring-2 ring-accent' : ''"
                                                    class="flex h-9 w-full items-center justify-between gap-1 rounded-lg bg-surface px-2.5 text-left text-[11px] transition">
                                                <span class="truncate" :class="row.bahan_baku_id ? '' : 'text-neutral-400'"
                                                      x-text="namaBahan(row.bahan_baku_id) || 'Pilih bahan'"></span>
                                                <svg class="h-3.5 w-3.5 shrink-0 text-neutral-500 transition-transform"
                                                     :class="dd === u.kode + '-' + i ? 'rotate-180' : ''"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>

                                            <div x-show="dd === u.kode + '-' + i" x-cloak x-transition.origin.top
                                                 class="absolute left-0 top-full z-20 mt-1 max-h-48 w-full min-w-[180px] overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">
                                                <template x-for="b in bahan" :key="b.id">
                                                    <button type="button"
                                                            @click="if (!bentrok(u.kode, b.id, i)) { row.bahan_baku_id = b.id; dd = null }"
                                                            :disabled="bentrok(u.kode, b.id, i)"
                                                            :class="row.bahan_baku_id === b.id
                                                                ? 'bg-cream font-semibold text-accent'
                                                                : (bentrok(u.kode, b.id, i) ? 'cursor-not-allowed text-neutral-300' : 'text-neutral-700 hover:bg-cream/60')"
                                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-[11px] transition">
                                                        <span class="truncate" x-text="b.nama"></span>
                                                        <svg x-show="row.bahan_baku_id === b.id" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none"
                                                             stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </template>
                                                <p x-show="bahan.length === 0" class="px-3 py-2 text-[10px] text-neutral-400">Belum ada bahan baku.</p>
                                            </div>
                                        </div>

                                        {{-- Takaran --}}
                                        <div class="relative">
                                            <input type="number" min="0" step="0.01" x-model="row.takaran" placeholder="0"
                                                   :name="`resep[${u.kode}][${i}][takaran]`"
                                                   class="h-9 w-full rounded-lg border-0 bg-surface pl-2 pr-8 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">
                                            <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-neutral-500"
                                                  x-text="satuan(row.bahan_baku_id)"></span>
                                        </div>

                                        <button type="button" @click="hapus(u.kode, i)" title="Hapus bahan" aria-label="Hapus bahan"
                                                class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:bg-red-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Berlaku untuk (hanya menu dengan varian) --}}
                                    <div x-show="m.varian" class="mt-1.5 flex items-center gap-1.5">
                                        <input type="hidden" :name="`resep[${u.kode}][${i}][temperatur]`" :value="row.temperatur">
                                        <span class="text-[9px] text-neutral-500">Berlaku untuk</span>
                                        <template x-for="opt in [['', 'Semua'], ['ice', 'Ice'], ['hot', 'Hot']]" :key="opt[0]">
                                            <button type="button" @click="row.temperatur = opt[0]"
                                                    :class="row.temperatur === opt[0] ? 'bg-accent text-white shadow-sm' : 'bg-white text-neutral-500 hover:bg-neutral-100'"
                                                    class="h-6 rounded-full px-2.5 text-[10px] font-semibold transition"
                                                    x-text="opt[1]"></button>
                                        </template>
                                    </div>

                                    {{-- Ikut level gula --}}
                                    <div x-show="m.varian" class="mt-1.5 flex items-center gap-1.5">
                                        <input type="hidden" :name="`resep[${u.kode}][${i}][ikut_gula]`" :value="row.ikut_gula ? 1 : 0">
                                        <button type="button" @click="row.ikut_gula = !row.ikut_gula"
                                                :class="row.ikut_gula ? 'bg-accent text-white shadow-sm' : 'bg-white text-neutral-500 hover:bg-neutral-100'"
                                                class="h-6 rounded-full px-2.5 text-[10px] font-semibold transition">
                                            Ikut level gula
                                        </button>
                                        <span class="text-[9px] text-neutral-400">Aktifkan untuk gula / sirup</span>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="tambah(u.kode)"
                                    class="text-[11px] font-medium text-accent hover:underline">+ Tambah bahan</button>
                        </div>
                    </template>

                    <div class="mt-3 grid grid-cols-[80px_1fr] gap-2 border-t border-neutral-200 pt-2">
                        <button type="button" @click="tutup()"
                                class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90">
                            Simpan resep
                        </button>
                    </div>
                </form>
            </template>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editResep', (daftar, bahan, bukaId, lama) => ({
        daftar,
        bahan,
        bukaId,
        id: null,
        tab: 'small',
        faktor: 1,
        dd: null,   // dropdown bahan yang sedang terbuka, format "ukuran-indeks"
        data: { small: [], medium: [], large: [] },

        get m() { return this.daftar.find(x => x.id === this.id) || null; },

        namaBahan(bid) {
            const b = this.bahan.find(b => String(b.id) === String(bid));
            return b ? b.nama : '';
        },
        satuan(bid) {
            const b = this.bahan.find(b => String(b.id) === String(bid));
            return b ? b.satuan : '';
        },
        // Bahan yang sama boleh dobel hanya kalau temperaturnya tidak tumpang tindih
        bentrok(u, bid, indeks) {
            const suhu = this.data[u][indeks]?.temperatur ?? '';
            return this.data[u].some((r, i) =>
                i !== indeks && String(r.bahan_baku_id) === String(bid) &&
                (r.temperatur === '' || suhu === '' || r.temperatur === suhu));
        },
        jumlah(u) { return (this.data[u] || []).length; },
        tambah(u) { this.data[u].push({ bahan_baku_id: '', takaran: '', temperatur: '', ikut_gula: false }); },
        buka(id) {
            const m = this.daftar.find(x => x.id === id);
            if (!m) return;
            this.data = JSON.parse(JSON.stringify({ small: [], medium: [], large: [], ...m.data }));
            if (this.data.small.length === 0) this.tambah('small');
            this.tab = 'small';
            this.faktor = 1;
            this.dd = null;
            this.id = id;

            // Di layar kecil panel ada di bawah daftar, jadi geser ke sana
            if (window.innerWidth < 1280) {
                this.$nextTick(() => this.$refs.panel.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            }
        },
        tutup() { this.id = null; this.dd = null; },

        tambah(u) { this.data[u].push({ bahan_baku_id: '', takaran: '', temperatur: '' , ikut_gula: false}); },
        hapus(u, i) {
            this.dd = null;
            this.data[u].splice(i, 1);
            if (u === 'small' && this.data.small.length === 0) this.tambah('small');
        },
        salin(u) {
            const label = this.m.ukuran.find(x => x.kode === u).label;
            if (this.data[u].length && !confirm('Ganti isi resep ' + label + ' dengan salinan dari Small?')) return;

            const f = parseFloat(this.faktor) || 1;
            this.data[u] = this.data.small
                .filter(r => r.bahan_baku_id !== '')
                .map(r => ({
                    bahan_baku_id: r.bahan_baku_id,
                    temperatur: r.temperatur || '',
                    takaran: Math.round((parseFloat(r.takaran) || 0) * f * 100) / 100,
                    ikut_gula: !!r.ikut_gula,
                }));
        },
        // Baris yang sama sekali kosong dibuang sebelum dikirim
        bersihkan() {
            for (const u of Object.keys(this.data)) {
                this.data[u] = this.data[u].filter(r =>
                    r.bahan_baku_id !== '' || (r.takaran !== '' && r.takaran !== null));
            }
        },
        kirim(e) {
            this.bersihkan();
            this.$nextTick(() => e.target.submit());
        },

        init() {
            if (!this.bukaId) return;
            this.buka(this.bukaId);
            // Kalau validasi gagal, pakai isian terakhir pengguna
            if (lama) {
                for (const u of Object.keys(lama)) {
                    this.data[u] = Object.values(lama[u] || {}).map(r => ({
                        bahan_baku_id: String(r.bahan_baku_id ?? ''),
                        takaran: r.takaran ?? '',
                        temperatur: r.temperatur ?? '',
                        ikut_gula: !!r.ikut_gula,
                    }));
                }
                if (this.data.small.length === 0) this.tambah('small');
            }
        },
    }));
});
</script>
@endpush