@extends('layouts.kasir')

@section('title', 'Persediaan Menu')

@section('content')
@php $angka = fn ($n) => \App\Models\BahanBaku::angka($n); @endphp

<div x-data="persediaan(@js($menu), @js($kategori), @js($konfig))"
     class="grid items-start gap-3 px-3 pb-3 pt-3 xl:grid-cols-[minmax(0,1fr)_320px]">

    {{-- ================= KIRI: DAFTAR MENU ================= --}}
    <section class="min-w-0">

        {{-- Pencarian Menu --}}
        <div class="relative mb-3 w-full">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <path d="M21 21l-4.3-4.3"/>
            </svg>

            <input id="cari-menu" type="text" x-model="cari"
                   placeholder="Cari menu..." autocomplete="off"
                   class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-9 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">

            <button type="button" x-show="cari.length > 0" @click="cari = ''"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400 transition hover:text-neutral-700"
                    aria-label="Hapus pencarian">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Chip kategori --}}
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="pilih(null)"
                    :class="kategoriAktif === null ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-400 hover:bg-neutral-300/70'"
                    class="flex h-7 items-center gap-1.5 rounded-full pl-[5px] pr-4 text-[11px] font-semibold transition">
                <span class="flex h-[18px] w-[18px] items-center justify-center rounded-full"
                      :class="kategoriAktif === null ? 'bg-white text-accent' : 'bg-white/80 text-neutral-400'">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/>
                        <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>
                    </svg>
                </span>
                Semua Menu
            </button>

            <template x-for="k in kategori" :key="k.id">
                <button type="button" @click="pilih(k.id)"
                        :class="kategoriAktif === k.id ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-400 hover:bg-neutral-300/70'"
                        class="flex h-7 items-center gap-1.5 rounded-full pl-[5px] pr-4 text-[11px] font-semibold transition">
                    <span class="flex h-[18px] w-[18px] items-center justify-center rounded-full"
                          :class="kategoriAktif === k.id ? 'bg-white text-accent' : 'bg-white/80 text-neutral-400'">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             x-html="ikon(k.nama)"></svg>
                    </span>
                    <span x-text="k.nama"></span>
                </button>
            </template>
        </div>

        {{-- Tabel --}}
        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm">

            <div class="hidden items-center bg-cream px-4 py-2.5 text-[11px] font-semibold text-accent lg:grid lg:grid-cols-[minmax(0,2.5fr)_1.2fr_1fr_1.3fr]">
                <span>Menu &amp; Kategori</span>
                <span class="text-center">Harga</span>
                <span class="text-center">Estimasi</span>
                <span class="text-center">Status</span>
            </div>

            <template x-for="m in daftar" :key="m.id">
                <div class="grid gap-2 border-t border-neutral-100 px-4 py-1.5 first:border-t-0 lg:grid-cols-[minmax(0,2.5fr)_1.2fr_1fr_1.3fr] lg:items-center">

                    {{-- Menu dan kategori --}}
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white">
                            <img x-show="m.gambar_url" :src="m.gambar_url" :alt="m.nama"
                                 class="h-full w-full object-contain" :class="m.status === 'habis' ? 'opacity-50 grayscale' : ''">
                            <span x-show="!m.gambar_url" class="text-base font-semibold text-neutral-300" x-text="m.nama.charAt(0)"></span>

                            {{-- Tanda dilarang untuk menu yang habis --}}
                            <span x-show="m.status === 'habis'"
                                  class="absolute inset-0 flex items-center justify-center bg-neutral-500/50 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="8"/><path d="M6.5 6.5l11 11"/></svg>
                            </span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-[11px] font-semibold"
                                   :class="m.status === 'habis' ? 'text-neutral-400 line-through' : ''" x-text="m.nama"></p>
                                <span class="rounded-full bg-cream px-2 py-px text-[9px] font-medium text-accent" x-text="m.kategori"></span>
                            </div>
                            <p class="mt-0.5 truncate text-[9px]" :class="kelasCatatan(m)" x-text="m.catatan"></p>
                        </div>
                    </div>

                    {{-- Harga --}}
                    <p class="text-[11px] font-semibold lg:text-center" x-text="harga(m)"></p>

                    {{-- Estimasi sisa --}}
                    <p class="text-[11px] font-semibold lg:text-center"
                       :class="m.porsi === 0 ? 'text-red-600' : ''" x-text="m.porsi + ' Porsi'"></p>

                    {{-- Indikator status (otomatis dari stok dan resep) --}}
                    <div class="flex items-center gap-3 lg:justify-center">
                        <span role="img" :aria-label="m.tersedia ? 'Tersedia' : 'Habis'"
                            :title="m.tersedia ? 'Tersedia' : 'Habis'"
                            :class="m.tersedia ? 'bg-accent' : 'bg-neutral-200'"
                            class="relative inline-flex h-6 w-10 shrink-0 items-center rounded-full transition">
                            <span :class="m.tersedia ? 'translate-x-5' : 'translate-x-1'"
                                class="inline-block h-4 w-4 rounded-full bg-white shadow transition"></span>
                        </span>
                    </div>
                </div>
            </template>

            <p x-show="daftar.length === 0" class="px-4 py-8 text-center text-xs text-neutral-400">Menu tidak ditemukan.</p>
        </div>
    </section>

    {{-- ================= KANAN: STOK MENIPIS ================= --}}
    <aside class="rounded-2xl bg-white px-4 pb-4 pt-5 shadow-sm xl:sticky xl:top-[62px]">

        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v2H9zM9 13l2 2 4-4"/>
            </svg>
            <h2 class="text-[15px] font-semibold">Stok Menipis</h2>
        </div>

        <div class="mt-2 border-t border-neutral-200 pt-2">
            <p class="text-[11px] leading-relaxed text-neutral-500">
                Pantauan stok kritis bahan baku yang mempengaruhi ketersediaan menu di kasir.
            </p>
        </div>

        <div class="mt-3 space-y-2">
            @forelse ($bahanMenipis as $b)
                <div class="rounded-lg bg-cream px-3 py-2">
                    <p class="text-[12px] font-semibold">{{ $b['nama'] }}</p>
                    <div class="mt-0.5 flex items-center justify-between gap-2 text-[10px] text-neutral-500">
                        <span>
                            Sisa:
                            <strong class="{{ $b['kritis'] ? 'text-red-600' : 'text-neutral-900' }}">{{ $angka($b['stok']) }}</strong>
                            / {{ $angka($b['penuh']) }} {{ $b['satuan'] }}
                        </span>
                        <span class="shrink-0">Min: {{ $angka($b['minimum']) }} {{ $b['satuan'] }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-200">
                        <div class="h-full rounded-full {{ $b['kritis'] ? 'bg-red-600' : 'bg-accent' }}" style="width: {{ $b['persen'] }}%"></div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg bg-green-50 px-3 py-6 text-center text-xs text-green-800">
                    <p class="font-semibold">Semua stok aman</p>
                    <p class="mt-1 text-[11px]">Belum ada bahan yang mendekati batas minimum.</p>
                </div>
            @endforelse

            @if ($lainnya > 0)
                <p class="text-center text-[11px] text-neutral-500">+ {{ $lainnya }} bahan lainnya mendekati batas minimum</p>
            @endif
        </div>

        <button type="button" @click="minta()" :disabled="kirim || @js($bahanMenipis->isEmpty())"
                class="mt-4 flex h-10 w-full items-center justify-center gap-2 rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.6 12.4a1 1 0 001 .8h9.8a1 1 0 001-.8L21 8H6"/>
            </svg>
            <span x-text="kirim ? 'Mengirim...' : 'Minta Restock ke Gudang'"></span>
        </button>
    </aside>

    {{-- Pemberitahuan singkat --}}
    <div x-show="pesan" x-cloak x-transition
         class="fixed bottom-5 left-1/2 z-[70] max-w-[90vw] -translate-x-1/2 rounded-lg bg-neutral-900 px-4 py-2 text-xs text-white shadow-lg"
         x-text="pesan"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('persediaan', (menu, kategori, konfig) => ({
        menu,
        kategori,
        konfig,
        kategoriAktif: null,   // null = Semua Menu
        cari: '',
        kirim: false,
        pesan: '',
        timer: null,

        get daftar() {
            const q = this.cari.trim().toLowerCase();

            return this.menu.filter(m =>
                (this.kategoriAktif === null || m.kategori_id === this.kategoriAktif) &&
                (q === '' || m.nama.toLowerCase().includes(q) || m.kategori.toLowerCase().includes(q))
            );
        },

        pilih(id) {
            this.kategoriAktif = id;
            this.cari = '';
        },

        rupiah(n) { return 'Rp ' + Math.round(Number(n)).toLocaleString('id-ID'); },
        harga(m) {
            return m.harga_min === m.harga_maks
                ? this.rupiah(m.harga_min)
                : this.rupiah(m.harga_min) + ' - ' + this.rupiah(m.harga_maks);
        },

        kelasCatatan(m) {
            return {
                bahaya: 'text-red-600',
                info: 'text-blue-700',
                peringatan: 'text-amber-700',
                netral: 'text-neutral-400',
            }[m.tipe_catatan];
        },

        ikon(nama) {
            const n = nama.toLowerCase();
            if (n.includes('non')) {
                return '<path d="M7 5h10l-1 14H8L7 5z"/><path d="M7.5 10h9"/>';
            }
            if (n.includes('tea') || n.includes('teh')) {
                return '<path d="M5 10h11v3a5 5 0 01-5 5h-1a5 5 0 01-5-5v-3z"/><path d="M16 11h1.5a2 2 0 010 4H16"/><path d="M10 6c0-1.5 1.5-1.5 1.5-3"/>';
            }
            if (n.includes('snack') || n.includes('makan') || n.includes('roti')) {
                return '<circle cx="12" cy="12" r="8"/><path d="M9 9h.01M14 8h.01M15 13h.01M10 14h.01"/>';
            }
            return '<path d="M6 9h10v4a4 4 0 01-4 4h-2a4 4 0 01-4-4V9z"/><path d="M16 10h1a2 2 0 010 4h-1"/><path d="M9 4v2M13 4v2"/>';
        },

        toast(teks) {
            this.pesan = teks;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.pesan = '', 3200);
        },

        // Ubah status manual. Saklar berubah langsung, lalu disesuaikan dengan jawaban server.
        async ubah(m) {
            if (m.proses) return;

            const lama = m.tersedia;
            m.tersedia = !lama;
            m.proses = true;

            try {
                const res = await fetch(this.konfig.urlToggle.replace('__ID__', m.id), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.konfig.csrf,
                    },
                    body: JSON.stringify({ is_active: !lama }),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    m.tersedia = lama;
                    if (data.menu) Object.assign(m, data.menu);
                    this.toast(res.status === 419
                        ? 'Sesi berakhir. Muat ulang halaman, lalu coba lagi.'
                        : (data.message || 'Status menu gagal diubah.'));
                    return;
                }

                Object.assign(m, data.menu);
                this.toast(data.message);
            } catch (e) {
                m.tersedia = lama;
                this.toast('Tidak bisa terhubung ke server. Periksa koneksi lalu coba lagi.');
            } finally {
                m.proses = false;
            }
        },

        async segarkan() {
            if (document.hidden) return;
            try {
                const ids = this.menu.map(x => x.id).join(',');
                const res = await fetch(this.konfig.urlStatus + '?ids=' + ids, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.menu.forEach(m => { if (!m.proses && data[m.id]) Object.assign(m, data[m.id]); });
            } catch (e) {}
        },

        init() { setInterval(() => this.segarkan(), 5000); },

        async minta() {
            if (this.kirim) return;
            this.kirim = true;

            try {
                const res = await fetch(this.konfig.urlRestock, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.konfig.csrf },
                });

                const data = await res.json().catch(() => ({}));
                this.toast(data.message || (res.ok ? 'Permintaan restock terkirim.' : 'Permintaan gagal dikirim.'));
            } catch (e) {
                this.toast('Tidak bisa terhubung ke server. Periksa koneksi lalu coba lagi.');
            } finally {
                this.kirim = false;
            }
        },
    }));
});
</script>
@endpush