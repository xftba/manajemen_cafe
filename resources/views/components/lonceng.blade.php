@props(['jumlah' => 0])

<div x-data="lonceng(@js([
         'url'       => route('notifikasi.index'),
         'baca'      => route('notifikasi.baca', '__ID__'),
         'bacaSemua' => route('notifikasi.baca-semua'),
         'csrf'      => csrf_token(),
     ]), {{ (int) $jumlah }})"
     class="relative">

    <button type="button" @click="buka = !buka" aria-label="Notifikasi"
            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-neutral-700 transition"
            :class="buka ? 'bg-accent text-white' : 'bg-neutral-100 hover:bg-accent hover:text-white'">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0112 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 003.4 0"/>
        </svg>
        <span x-show="total > 0" x-cloak x-text="total > 9 ? '9+' : total"
              class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold text-white ring-2 ring-white"></span>
    </button>

    <div x-show="buka" x-cloak @click.outside="buka = false" x-transition
         class="absolute right-0 top-full z-50 mt-2 w-[290px] max-w-[calc(100vw-24px)] rounded-xl border border-neutral-200 bg-white p-3 shadow-xl">

        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-[13px] font-semibold">Notifikasi</h3>
                <p class="mt-0.5 text-[11px] text-neutral-500">Informasi dan peringatan sistem café.</p>
            </div>
            <button type="button" x-show="belum > 0" @click="bacaSemua()"
                    class="shrink-0 text-[10px] font-semibold text-accent hover:underline">Tandai dibaca</button>
        </div>

        <div class="mt-2 max-h-80 space-y-1.5 overflow-y-auto">

            {{-- Notifikasi berbasis kejadian (dari tabel notifikasi) --}}
            <template x-for="n in daftar" :key="n.id">
                <button type="button" @click="klik(n)"
                        class="block w-full rounded-lg px-3 py-2 text-left transition"
                        :class="n.dibaca ? 'bg-neutral-100 opacity-70 hover:opacity-100' : gaya(n.jenis).kotak">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-[11px] font-semibold" x-text="n.judul"></p>
                        <span class="flex shrink-0 items-center gap-1 text-[9px] text-neutral-400">
                            <span x-show="!n.dibaca" class="h-1.5 w-1.5 rounded-full" :class="gaya(n.jenis).titik"></span>
                            <span x-text="n.waktu"></span>
                        </span>
                    </div>
                    <p x-show="n.isi" class="mt-0.5 text-[10px] leading-snug text-neutral-600" x-text="n.isi"></p>
                </button>
            </template>

            {{-- Notifikasi dihitung langsung (stok menipis, menu habis) --}}
            {{ $slot }}

            <div x-show="total === 0 && daftar.length === 0" class="py-4 text-center text-[11px] text-neutral-400">
                Belum ada notifikasi.
            </div>
        </div>
    </div>

    <div x-show="pesan" x-cloak x-transition
         class="fixed bottom-5 left-1/2 z-[70] max-w-[90vw] -translate-x-1/2 rounded-lg bg-neutral-900 px-4 py-2 text-xs text-white shadow-lg"
         x-text="pesan"></div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lonceng', (konfig, jumlahStatis) => ({
        konfig,
        jumlahStatis,
        buka: false,
        daftar: [],
        belum: 0,
        pesan: '',
        awal: true,

        get total() { return this.jumlahStatis + this.belum; },

        gaya(jenis) {
            return {
                restock:      { kotak: 'bg-blue-50 ring-1 ring-blue-200 hover:bg-blue-100',    titik: 'bg-blue-500' },
                pengajuan:    { kotak: 'bg-amber-50 ring-1 ring-amber-200 hover:bg-amber-100', titik: 'bg-amber-500' },
                disetujui:    { kotak: 'bg-green-50 ring-1 ring-green-200 hover:bg-green-100', titik: 'bg-green-600' },
                menu_tersedia:{ kotak: 'bg-green-50 ring-1 ring-green-200 hover:bg-green-100', titik: 'bg-green-600' },
                ditolak:      { kotak: 'bg-red-50 ring-1 ring-red-200 hover:bg-red-100',       titik: 'bg-red-600' },
                batal:        { kotak: 'bg-red-50 ring-1 ring-red-200 hover:bg-red-100',       titik: 'bg-red-600' },
                rusak:        { kotak: 'bg-red-50 ring-1 ring-red-200 hover:bg-red-100',       titik: 'bg-red-600' },
            }[jenis] || { kotak: 'bg-neutral-50 ring-1 ring-neutral-200 hover:bg-neutral-100', titik: 'bg-neutral-500' };
        },

        async kirim(url) {
            try {
                await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.konfig.csrf } });
            } catch (e) {}
        },

        async klik(n) {
            if (!n.dibaca) {
                n.dibaca = true;
                this.belum = Math.max(0, this.belum - 1);
                await this.kirim(this.konfig.baca.replace('__ID__', n.id));
            }
            if (n.url) window.location.href = n.url;
        },

        async bacaSemua() {
            this.daftar.forEach(n => n.dibaca = true);
            this.belum = 0;
            await this.kirim(this.konfig.bacaSemua);
        },

        async ambil() {
            if (document.hidden) return;
            try {
                const res = await fetch(this.konfig.url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                // Toast hanya untuk notifikasi yang masuk setelah halaman terbuka
                if (!this.awal) {
                    const baru = data.daftar.filter(n => !n.dibaca && !this.daftar.some(d => d.id === n.id));
                    if (baru.length) {
                        this.pesan = baru[0].judul;
                        setTimeout(() => this.pesan = '', 4000);
                    }
                }
                this.daftar = data.daftar;
                this.belum = data.belum;
                this.awal = false;
            } catch (e) {}
        },

        init() {
            this.ambil();
            setInterval(() => this.ambil(), 10000);
        },
    }));
});
</script>
@endpush
@endonce