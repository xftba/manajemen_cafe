@extends('layouts.kasir')

@section('title', 'Pos Kasir')

@section('content')
<div
    x-data="pos(@js($menu), @js($kategori), @js($konfig))"
    @keydown.escape.window="popup = false"
    class="grid items-start gap-3 px-3 pb-3 pt-3 xl:grid-cols-[minmax(0,1fr)_300px]">

    {{-- ================= KIRI: MENU ================= --}}
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

            {{-- Tombol hapus pencarian --}}
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
            <template x-for="k in kategori" :key="k.id">
                <button type="button" @click="pilihKategori(k.id)"
                        :class="(kategoriAktif === k.id && !cari) ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-400 hover:bg-neutral-300/70'"
                        class="flex h-7 items-center gap-1.5 rounded-full pl-[5px] pr-4 text-[11px] font-semibold transition">
                    <span class="flex h-[18px] w-[18px] items-center justify-center rounded-full"
                          :class="(kategoriAktif === k.id && !cari) ? 'bg-white text-accent' : 'bg-white/80 text-neutral-400'">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             x-html="ikon(k.nama)"></svg>
                    </span>
                    <span x-text="k.nama"></span>
                </button>
            </template>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <h2 class="text-[13px] font-semibold" x-text="judulDaftar"></h2>
            <span class="text-[11px] text-neutral-400" x-text="siap + ' menu siap'"></span>
        </div>

        {{-- Grid menu --}}
        <div class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <template x-for="m in daftar" :key="m.id">
                <div class="group flex max-h-[150px] flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-md"
                     :class="{ 'opacity-60': !bisaTambah(m) }">
                    <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 pt-2">
                        <img x-show="m.gambar_url" :src="m.gambar_url" :alt="m.nama"
                             class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                        <span x-show="!m.gambar_url" class="text-4xl font-semibold text-neutral-200" x-text="m.nama.charAt(0)"></span>
                        <span x-show="!bisaTambah(m)" x-text="alasan(m)"
                              class="absolute left-2 top-2 rounded-full bg-red-100 px-2 py-px text-[9px] font-semibold text-red-700"></span>
                    </div>
                    <div class="flex shrink-0 items-end justify-between gap-2 px-3 pb-2.5 pt-1.5">
                        <div class="min-w-0">
                            <p class="truncate text-[10px] font-semibold" x-text="m.nama"></p>
                            <p class="text-[9px] text-neutral-400" x-text="rupiah(m.harga)"></p>
                            <p x-show="bisaTambah(m) && sisa(m.id) <= 5" class="text-[9px] text-amber-700"
                               x-text="'Sisa ' + sisa(m.id) + ' porsi'"></p>
                        </div>
                        <button type="button" @click="buka(m)" :disabled="!bisaTambah(m)" aria-label="Tambah"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-white shadow-sm transition hover:opacity-90 active:scale-95 disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:shadow-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <p x-show="daftar.length === 0" class="col-span-full py-8 text-center text-xs text-neutral-400">
                Menu tidak ditemukan.
            </p>
        </div>
    </section>

    {{-- ================= KANAN: PESANAN ================= --}}
    <aside class="rounded-2xl bg-white px-4 pb-3 pt-4 shadow-sm
              xl:sticky xl:top-[62px] xl:flex xl:h-[calc(100vh-90px)] xl:flex-col xl:overflow-y-auto">

        <div class="flex items-center justify-between">
            <h2 class="text-[13px] font-semibold">Pesanan</h2>
            <span class="text-[11px] font-semibold text-neutral-400" x-text="'Order #' + konfig.nomorOrder"></span>
        </div>

        {{-- Takeaway / Dine In --}}
        <div class="mt-2 grid grid-cols-2 rounded-lg bg-cream p-1 text-[11px] font-semibold">
            <button type="button" @click="tipe = 'takeaway'"
                    :class="tipe === 'takeaway' ? 'bg-accent text-white shadow-sm' : 'text-neutral-900'"
                    class="h-7 rounded-lg transition">Takeaway</button>
            <button type="button" @click="tipe = 'dine_in'"
                    :class="tipe === 'dine_in' ? 'bg-accent text-white shadow-sm' : 'text-neutral-900'"
                    class="h-7 rounded-lg transition">Dine In</button>
        </div>

        <label class="mt-2 block text-[11px] text-neutral-700">Nama customer</label>
        <input type="text" x-model="customer" maxlength="100"
               class="mt-1 h-8 w-full rounded-lg border-0 bg-white px-3 text-[11px] shadow-[0_1px_4px_rgba(0,0,0,0.18)] focus:outline-none focus:ring-2 focus:ring-accent">

        <p class="mt-2 text-[11px] text-neutral-700">Daftar pesanan</p>

        <div class="mt-1.5 min-h-[120px] flex-1 space-y-2 overflow-y-auto px-0.5 pb-2 pt-0.5">
            <template x-for="item in keranjang" :key="item.key">
                <div class="flex justify-between gap-2 rounded-lg bg-white p-2 shadow-[0_1px_6px_rgba(0,0,0,0.18)]">
                    <div class="flex min-w-0 flex-1 flex-col justify-between gap-2">
                        <div class="min-w-0">
                            <p class="break-words text-[11px] font-semibold leading-snug" x-text="item.nama"></p>
                            <p x-show="varianTeks(item)" class="mt-0.5 text-[9px] text-neutral-400" x-text="varianTeks(item)"></p>
                            <p x-show="item.catatan" class="mt-0.5 break-words text-[9px] italic text-neutral-400"
                               x-text="'“' + item.catatan + '”'"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold" x-text="rupiah(item.harga)"></p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <button type="button" @click="ganti(item)"
                                        class="inline-flex h-6 items-center gap-1 rounded-full border border-neutral-300 px-2.5 text-[10px] text-neutral-700 transition hover:bg-neutral-50">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>
                                    Ganti
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-end justify-between">
                        <div class="flex h-12 w-12 items-center justify-center">
                            <img x-show="item.gambar_url" :src="item.gambar_url" :alt="item.nama" class="h-full w-full object-contain">
                            <span x-show="!item.gambar_url" class="text-2xl font-semibold text-neutral-200" x-text="item.nama.charAt(0)"></span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <button type="button" @click="ubah(item, -1)" aria-label="Kurangi"
                                    class="flex h-5 w-5 items-center justify-center rounded-full border border-accent text-accent transition hover:bg-accent/10">
                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14"/></svg>
                            </button>
                            <span class="w-4 text-center text-[11px] font-semibold" x-text="item.jumlah"></span>
                            <button type="button" @click="ubah(item, 1)" aria-label="Tambah"
                                    class="flex h-5 w-5 items-center justify-center rounded-full border border-accent text-accent transition hover:bg-accent/10">
                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <p x-show="keranjang.length === 0" class="py-8 text-center text-[11px] text-neutral-400">
                Belum ada pesanan. Pilih menu di sebelah kiri.
            </p>
        </div>

        {{-- Total --}}
        <dl class="mt-1 space-y-1.5 border-t border-neutral-200 pt-3 text-[11px]">
            <div class="flex justify-between font-semibold">
                <dt>Subtotal</dt><dd x-text="rupiah(subtotal)"></dd>
            </div>
            <div class="flex justify-between text-neutral-400">
                <dt x-text="'Pajak (' + pajakPersen + '%)'"></dt>
                <dd class="font-semibold text-neutral-900" x-text="rupiah(pajak)"></dd>
            </div>
            <div class="flex justify-between border-t border-dashed border-neutral-300 pt-2 text-[12px] font-semibold">
                <dt>Total</dt><dd x-text="rupiah(total)"></dd>
            </div>
        </dl>

        <button type="button" @click="bukaBayar()" :disabled="!bisaProses"
                class="mt-2 h-9 w-full shrink-0 rounded-lg bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-40">
            Bayar
        </button>
    </aside>

    {{-- Pemberitahuan singkat --}}
    <div x-show="pesan" x-cloak x-transition
         class="fixed bottom-5 left-1/2 z-[70] max-w-[90vw] -translate-x-1/2 rounded-lg bg-neutral-900 px-4 py-2 text-xs text-white shadow-lg"
         x-text="pesan"></div>

    {{-- ================= POPUP VARIAN ================= --}}
    <div x-show="popup" x-cloak @click.self="popup = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

        <div class="max-h-[90vh] w-full max-w-[340px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">

            {{-- Informasi Menu --}}
            <div class="flex items-start gap-3 border-b border-neutral-200 pb-3">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface">
                    <img x-show="menuPopup && menuPopup.gambar_url"
                         :src="menuPopup ? menuPopup.gambar_url : ''"
                         :alt="menuPopup ? menuPopup.nama : ''"
                         class="h-full w-full object-contain">
                    <span x-show="menuPopup && !menuPopup.gambar_url"
                          class="text-2xl font-semibold text-neutral-200"
                          x-text="menuPopup ? menuPopup.nama.charAt(0) : ''"></span>
                </div>

                <div class="min-w-0 pt-0.5">
                    <h3 class="text-[12px] font-semibold" x-text="menuPopup ? menuPopup.nama : ''"></h3>
                    <p x-show="menuPopup && menuPopup.deskripsi"
                       class="mt-0.5 text-[9px] leading-snug text-accent"
                       x-text="menuPopup ? menuPopup.deskripsi : ''"></p>
                    <p class="mt-0.5 text-[11px] font-semibold" x-text="rupiah(hargaPopup)"></p>
                </div>
            </div>

            {{-- Pilihan Varian --}}
            <template x-if="menuPopup && menuPopup.pakai_varian">
                <div>

                    {{-- Temperature --}}
                    <div class="mt-2.5 flex items-center justify-between">
                        <p class="text-[11px] font-semibold">Temperature</p>
                        <p class="text-[9px] text-neutral-400">Select 1</p>
                    </div>

                    <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                        <template x-for="[kode, teks] in Object.entries(opsi.temperatur)" :key="kode">
                            <button type="button" @click="f.temperatur = kode"
                                    :class="f.temperatur === kode
                                        ? 'border-accent bg-white text-neutral-900'
                                        : 'border-transparent bg-surface text-neutral-400'"
                                    class="flex h-9 items-center justify-center gap-1.5 rounded-lg border text-[10px] font-medium transition">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                     :class="f.temperatur === kode ? 'text-accent' : 'text-neutral-400'"
                                     x-html="ikonTemperatur(kode)"></svg>
                                <span x-text="teks"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Size --}}
                    <div class="mt-2.5 flex items-center justify-between">
                        <p class="text-[11px] font-semibold">Size</p>
                        <p class="text-[9px] text-neutral-400">Select 1</p>
                    </div>

                    <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                        <template x-for="[kode, teks] in Object.entries(opsi.ukuran)" :key="kode">
                            <button type="button" @click="f.ukuran = kode" :disabled="!ukuranBisa(kode)"
                                    :class="f.ukuran === kode
                                        ? 'border-accent bg-white text-neutral-900'
                                        : 'border-transparent bg-surface text-neutral-400'"
                                    class="flex h-9 flex-col items-center justify-center rounded-lg border text-[10px] font-medium transition disabled:cursor-not-allowed disabled:opacity-40">
                                <span x-text="teks"></span>
                                <span x-show="ukuranBisa(kode) && tambahanUkuran(kode) > 0"
                                      class="text-[8px] font-normal text-accent"
                                      x-text="'+' + rupiah(tambahanUkuran(kode))"></span>
                                <span x-show="!ukuranBisa(kode)"
                                      class="text-[8px] font-normal text-red-600"
                                      x-text="alasanUkuran(kode)"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Sugar Level --}}
                    <div class="mt-2.5 flex items-center justify-between">
                        <p class="text-[11px] font-semibold">Sugar Level</p>
                        <p class="text-[9px] text-neutral-400">Select 1</p>
                    </div>

                    <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                        <template x-for="[kode, teks] in Object.entries(opsi.gula)" :key="kode">
                            <button type="button" @click="f.gula = kode"
                                    :class="f.gula === kode
                                        ? 'border-accent bg-white text-neutral-900'
                                        : 'border-transparent bg-surface text-neutral-400'"
                                    class="flex h-9 items-center justify-center rounded-lg border text-[10px] font-medium transition"
                                    x-text="teks"></button>
                        </template>
                    </div>

                </div>
            </template>

            {{-- Catatan Tambahan --}}
            <div class="mt-2.5 flex items-center justify-between">
                <p class="text-[11px] font-semibold">Catatan Tambahan</p>
                <p class="text-[9px] text-neutral-400" x-text="f.catatan.length + '/100'"></p>
            </div>

            <textarea x-model="f.catatan" maxlength="100" rows="2" placeholder="Tambahkan catatan..."
                      class="mt-1.5 w-full resize-none rounded-lg border-0 bg-surface px-3 py-2 text-[10px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent"></textarea>

            {{-- Tombol Aksi --}}
            <div class="mt-3 grid grid-cols-[80px_1fr] gap-2">
                <button type="button" @click="popup = false"
                        class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                    Batal
                </button>
                <button type="button" @click="simpan()"
                        class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90"
                        x-text="f.editKey ? 'Simpan perubahan' : 'Tambah ke pesanan'"></button>
            </div>

        </div>
    </div>

    {{-- ================= POPUP PEMBAYARAN ================= --}}
    <div x-show="modal === 'bayar'" x-cloak
         @keydown.escape.window="if (!memproses) { modal = null; galat = '' }"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

        <div class="max-h-[90vh] w-full max-w-[340px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">

            <h3 class="text-[13px] font-semibold">Pembayaran Pesanan</h3>

            {{-- Total tagihan --}}
            <div class="mt-2.5 flex items-center justify-between rounded-xl bg-cream px-3 py-2.5">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-accent">Total Tagihan</p>
                    <p class="mt-0.5 text-[18px] font-semibold leading-tight" x-text="rupiah(total)"></p>
                    <p class="mt-0.5 text-[9px] text-neutral-500"
                       x-text="pajakPersen > 0 ? 'Sudah termasuk pajak ' + pajakPersen + '%' : 'Tanpa pajak'"></p>
                </div>

                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/>
                        <path d="M9 8h6M9 12h6"/>
                    </svg>
                </div>
            </div>

            {{-- Metode pembayaran --}}
            <p class="mt-2.5 text-[11px] font-semibold">Metode Pembayaran</p>

            <div class="mt-1.5 grid grid-cols-2 rounded-lg bg-cream p-1 text-[11px] font-semibold">
                <button type="button" @click="metode = 'tunai'; galat = ''"
                        :class="metode === 'tunai' ? 'bg-accent text-white shadow-sm' : 'text-neutral-900'"
                        class="flex h-7 items-center justify-center gap-1.5 rounded-lg transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <circle cx="12" cy="12" r="2.5"/>
                        <path d="M6 10v.01M18 14v.01"/>
                    </svg>
                    Tunai/Cash
                </button>

                <button type="button" @click="metode = 'qris'; galat = ''"
                        :class="metode === 'qris' ? 'bg-accent text-white shadow-sm' : 'text-neutral-900'"
                        class="flex h-7 items-center justify-center gap-1.5 rounded-lg transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <path d="M14 14h3v3M21 14v.01M14 21h3M21 17v4"/>
                    </svg>
                    Qris
                </button>
            </div>

            {{-- Tunai --}}
            <div x-show="metode === 'tunai'">

                <p class="mt-2.5 text-[11px] font-semibold">Uang Diterima</p>

                <input x-ref="uang" type="text" inputmode="numeric" autocomplete="off" placeholder="Rp 0"
                       :value="uangTeks" @input="ketikUang($event)" @keydown.enter.prevent="konfirmasi()"
                       class="mt-1.5 h-9 w-full rounded-lg border-0 bg-cream px-3 text-[12px] font-semibold placeholder:font-normal placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">

                {{-- Uang cepat --}}
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    <template x-for="(n, i) in opsiUang" :key="n">
                        <button type="button" @click="uangDiterima = n"
                                class="rounded-full border border-neutral-300 px-2.5 py-1 text-[9px] font-medium transition hover:bg-neutral-50"
                                x-text="i === 0 ? 'Uang Pas' : rupiah(n)"></button>
                    </template>
                </div>

                {{-- Uang kembalian --}}
                <div class="mt-2.5 flex items-center justify-between rounded-xl bg-cream px-3 py-2.5">
                    <div>
                        <p class="text-[11px] font-semibold"
                           x-text="(cukup || uangDiterima === '') ? 'Uang Kembalian' : 'Uang Kurang'"></p>
                        <p class="mt-0.5 text-[16px] font-semibold leading-tight"
                           :class="(cukup || uangDiterima === '') ? 'text-neutral-900' : 'text-red-600'"
                           x-text="rupiah(selisihUang)"></p>
                    </div>

                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 0115-6.7L21 8M21 3v5h-5M21 12a9 9 0 01-15 6.7L3 16M3 21v-5h5"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- QRIS --}}
            <div x-show="metode === 'qris'" class="mt-4 text-center">

                <template x-if="konfig.qrisUrl">
                    <img :src="konfig.qrisUrl" alt="QRIS" class="mx-auto h-36 w-36 object-contain">
                </template>

                <template x-if="!konfig.qrisUrl">
                    <div class="mx-auto flex h-36 w-36 items-center justify-center rounded-lg border border-dashed border-neutral-300 px-3 text-[10px] text-neutral-400">
                        Gambar QRIS belum diatur. Admin dapat mengunggahnya di Pengaturan Café.
                    </div>
                </template>

                <p class="mt-1.5 text-[11px] font-semibold" x-text="konfig.namaCafe"></p>
            </div>

            {{-- Pesan error --}}
            <p x-show="galat" x-text="galat"
               class="mt-2.5 rounded-lg bg-red-100 px-3 py-1.5 text-[10px] text-red-800"></p>

            {{-- Tombol aksi --}}
            <div class="mt-4 grid grid-cols-[80px_1fr] gap-2">
                <button type="button" @click="modal = null; galat = ''" :disabled="memproses"
                        class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5 disabled:opacity-50">
                    Batal
                </button>

                <button type="button" @click="konfirmasi()" :disabled="!cukup || memproses"
                        class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                        x-text="memproses ? 'Memproses...' : 'Proses Pembayaran'"></button>
            </div>

        </div>
    </div>

    {{-- ================= POPUP SUKSES ================= --}}
    <div x-show="modal === 'sukses'" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

        <div class="w-full max-w-[300px] rounded-2xl bg-white p-4 text-center shadow-xl">

            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h3 class="mt-2 text-sm font-semibold">Transaksi berhasil</h3>

            <p class="mt-0.5 text-[11px] text-neutral-500" x-text="hasil ? hasil.invoice : ''"></p>

            <dl class="mt-3 space-y-1.5 rounded-xl bg-cream px-3 py-2.5 text-[11px]">
                <div class="flex justify-between">
                    <dt class="text-neutral-600">Total</dt>
                    <dd class="font-semibold" x-text="hasil ? rupiah(hasil.total) : ''"></dd>
                </div>

                <div class="flex justify-between" x-show="hasil && hasil.metode === 'tunai'">
                    <dt class="text-neutral-600">Kembalian</dt>
                    <dd class="font-semibold" x-text="hasil ? rupiah(hasil.kembalian) : ''"></dd>
                </div>
            </dl>

            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" @click="cetak()"
                        class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                    Cetak Struk
                </button>

                <button type="button" @click="pesananBaru()"
                        class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90">
                    Pesanan Baru
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pos', (menu, kategori, konfig) => ({
        menu,
        kategori,
        konfig,
        opsi: konfig.opsi,
        stokAwal: Object.fromEntries(konfig.bahan.map(b => [b.id, b.stok])),
        kategoriAktif: kategori.length ? kategori[0].id : null,
        cari: '',
        tipe: 'takeaway',
        customer: '',
        metode: 'tunai',
        keranjang: [],

        // popup varian
        popup: false,
        f: { menuId: null, temperatur: 'ice', ukuran: 'small', gula: 'normal', catatan: '', editKey: null },

        // popup pembayaran dan sukses
        modal: null,           // null | 'bayar' | 'sukses'
        uangDiterima: '',
        noReferensi: '',
        memproses: false,
        galat: '',
        hasil: null,
        pesan: '',
        timer: null,

        get pajakPersen() { return this.konfig.pajakPersen; },

        get daftar() {
            const q = this.cari.trim().toLowerCase();
            return this.menu.filter(m => q
                ? m.nama.toLowerCase().includes(q)
                : m.kategori_id === this.kategoriAktif);
        },
        get judulDaftar() {
            if (this.cari.trim()) return 'Hasil Pencarian';
            const k = this.kategori.find(k => k.id === this.kategoriAktif);
            return k ? k.nama + ' Menu' : 'Menu';
        },
        get siap() { return this.daftar.filter(m => this.bisaTambah(m)).length; },
        get subtotal() { return this.keranjang.reduce((t, i) => t + i.harga * i.jumlah, 0); },
        get pajak() { return Math.round(this.subtotal * this.pajakPersen / 100); },
        get total() { return this.subtotal + this.pajak; },
        get bisaProses() { return this.keranjang.length > 0; },

        // ----- uang tunai -----
        get kembalian() { return (Number(this.uangDiterima) || 0) - this.total; },
        get selisihUang() { return this.uangDiterima === '' ? 0 : Math.abs(this.kembalian); },
        get uangTeks() { return this.uangDiterima === '' ? '' : this.rupiah(this.uangDiterima); },
        get cukup() {
            return this.metode === 'qris' ? true : (Number(this.uangDiterima) || 0) >= this.total;
        },
        get opsiUang() {
            const t = this.total;
            const set = new Set([
                t,
                Math.ceil(t / 10000) * 10000,
                Math.ceil(t / 50000) * 50000,
                Math.ceil(t / 100000) * 100000,
            ]);
            return [...set].filter(n => n >= t).sort((a, b) => a - b).slice(0, 4);
        },
        ketikUang(e) {
            const angka = e.target.value.replace(/\D/g, '').slice(0, 9);
            this.uangDiterima = angka === '' ? '' : Number(angka);
            e.target.value = this.uangTeks;
        },

        // ----- harga -----
        get menuPopup() { return this.menu.find(m => m.id === this.f.menuId) || null; },
        get hargaPopup() { return this.menuPopup ? this.hargaUkuran(this.menuPopup, this.f.ukuran) : 0; },

        hargaUkuran(m, ukuran) {
            if (!m.pakai_varian) return m.harga;
            if (ukuran === 'medium') return m.harga + m.tambahan_medium;
            if (ukuran === 'large') return m.harga + m.tambahan_large;
            return m.harga;
        },
        tambahanUkuran(kode) {
            const m = this.menuPopup;
            if (!m) return 0;
            if (kode === 'medium') return m.tambahan_medium;
            if (kode === 'large') return m.tambahan_large;
            return 0;
        },

        // ----- stok: dihitung per bahan dari resep ukuran masing-masing -----
        daftarUkuran(m) { return m.pakai_varian ? Object.keys(this.opsi.ukuran) : ['small']; },

        resepUntuk(m, ukuran) {
            const kunci = m.pakai_varian ? (ukuran || 'small') : 'small';
            return (m.resep && m.resep[kunci]) || [];
        },

        // Total bahan yang sudah dipakai pesanan saat ini ([bahan_id => jumlah])
        pakai(kecualiKey = null) {
            const total = {};
            for (const i of this.keranjang) {
                if (i.key === kecualiKey) continue;
                const m = this.menu.find(x => x.id === i.menu_id);
                for (const r of this.resepUntuk(m, i.ukuran)) {
                    total[r.bahan_id] = (total[r.bahan_id] || 0) + i.jumlah * r.takaran;
                }
            }
            return total;
        },

        bisaTambahUkuran(m, ukuran, jumlah = 1, kecualiKey = null) {
            const resep = this.resepUntuk(m, ukuran);
            if (!resep.length) return false;
            const dipakai = this.pakai(kecualiKey);
            return resep.every(r =>
                (this.stokAwal[r.bahan_id] || 0) - (dipakai[r.bahan_id] || 0) + 1e-6 >= jumlah * r.takaran);
        },
        punyaResep(m) { return this.daftarUkuran(m).some(u => this.resepUntuk(m, u).length > 0); },
        bisaTambah(m) {
            return this.daftarUkuran(m).some(u => this.bisaTambahUkuran(m, u));
        },
        alasan(m) {
            if (!this.punyaResep(m)) return 'Tanpa resep';
            return 'Stok habis';
        },
        // Porsi terbanyak yang masih bisa ditambahkan, dari ukuran mana pun
        sisa(menuId) {
            const m = this.menu.find(x => x.id === menuId);
            if (!m) return 0;
            const dipakai = this.pakai();
            let maks = 0;

            for (const u of this.daftarUkuran(m)) {
                const resep = this.resepUntuk(m, u);
                if (!resep.length) continue;
                const porsi = Math.min(...resep.map(r =>
                    Math.floor(((this.stokAwal[r.bahan_id] || 0) - (dipakai[r.bahan_id] || 0) + 1e-6) / r.takaran)));
                maks = Math.max(maks, porsi);
            }
            return Math.max(0, maks);
        },

        // Apakah ukuran ini bisa dipilih di popup (resep ada dan stok cukup)
        ukuranBisa(kode) {
            const m = this.menuPopup;
            if (!m) return false;
            const edit = this.f.editKey ? this.keranjang.find(i => i.key === this.f.editKey) : null;
            return this.bisaTambahUkuran(m, kode, edit ? edit.jumlah : 1, edit ? edit.key : null);
        },
        alasanUkuran(kode) {
            const m = this.menuPopup;
            if (!m) return '';
            return this.resepUntuk(m, kode).length ? 'Stok kurang' : 'Belum ada resep';
        },

        // ----- kategori dan pencarian -----
        pilihKategori(id) {
            this.kategoriAktif = id;
            this.cari = '';
            const kolom = document.getElementById('cari-menu');
            if (kolom) kolom.value = '';
        },

        // ----- popup varian -----
        buka(m) {
            if (!this.bisaTambah(m)) return;
            const ukuran = this.daftarUkuran(m).find(u => this.bisaTambahUkuran(m, u)) || 'small';
            this.f = { menuId: m.id, temperatur: 'ice', ukuran, gula: 'normal', catatan: '', editKey: null };
            this.popup = true;
        },
        ganti(item) {
            this.f = {
                menuId: item.menu_id,
                temperatur: item.temperatur || 'ice',
                ukuran: item.ukuran || 'small',
                gula: item.gula || 'normal',
                catatan: item.catatan || '',
                editKey: item.key,
            };
            this.popup = true;
        },
        kunci(b) {
            return [b.menu_id, b.temperatur, b.ukuran, b.gula, b.catatan].join('|');
        },
        simpan() {
            const m = this.menuPopup;
            if (!m) return;

            if (!this.ukuranBisa(this.f.ukuran)) {
                this.toast('Stok bahan ' + m.nama + ' tidak cukup, atau resep ukuran ini belum dibuat.');
                return;
            }

            const v = m.pakai_varian;
            const baris = {
                key: '',
                menu_id: m.id,
                nama: m.nama,
                gambar_url: m.gambar_url,
                temperatur: v ? this.f.temperatur : null,
                ukuran: v ? this.f.ukuran : null,
                gula: v ? this.f.gula : null,
                harga: this.hargaUkuran(m, this.f.ukuran),
                catatan: this.f.catatan.trim(),
                jumlah: 1,
            };
            baris.key = this.kunci(baris);

            if (this.f.editKey) {
                const idx = this.keranjang.findIndex(i => i.key === this.f.editKey);
                if (idx === -1) { this.popup = false; return; }

                const lama = this.keranjang[idx];
                baris.jumlah = lama.jumlah;

                const sama = this.keranjang.find((i, n) => n !== idx && i.key === baris.key);
                if (sama) {
                    sama.jumlah += lama.jumlah;           // varian kembar digabung
                    this.keranjang.splice(idx, 1);
                } else {
                    this.keranjang.splice(idx, 1, baris);
                }
            } else {
                const ada = this.keranjang.find(i => i.key === baris.key);
                if (ada) this.ubah(ada, 1);
                else this.keranjang.push(baris);
            }

            this.popup = false;
        },

        varianTeks(item) {
            return [
                item.temperatur ? this.opsi.temperatur[item.temperatur] : null,
                item.ukuran ? this.opsi.ukuran[item.ukuran] : null,
                item.gula ? this.opsi.gula[item.gula] : null,
            ].filter(Boolean).join(' • ');
        },

        ubah(item, delta) {
            if (delta > 0) {
                const m = this.menu.find(x => x.id === item.menu_id);

                if (!this.bisaTambahUkuran(m, item.ukuran, 1)) {
                    this.toast('Stok bahan ' + item.nama + ' tidak cukup untuk menambah porsi lagi.');
                    return;
                }
                if (item.jumlah >= 99) return;
            }

            item.jumlah += delta;
            if (item.jumlah <= 0) {
                this.keranjang = this.keranjang.filter(i => i.key !== item.key);
            }
        },
        hapus(item) {
            this.keranjang = this.keranjang.filter(i => i.key !== item.key);
        },

        toast(teks) {
            this.pesan = teks;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.pesan = '', 2800);
        },
        rupiah(n) { return 'Rp ' + Math.round(Number(n)).toLocaleString('id-ID'); },

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
        ikonTemperatur(kode) {
            return kode === 'hot'
                ? '<path d="M5 10h11v3a5 5 0 01-5 5h-1a5 5 0 01-5-5v-3z"/><path d="M16 11h1.5a2 2 0 010 4H16"/><path d="M9 4v2M13 4v2"/>'
                : '<path d="M7 8h10l-1 12H8L7 8z"/><path d="M12 8V3l3-1"/><path d="M7.5 12h9"/>';
        },

        // ----- pembayaran -----
        bukaBayar() {
            if (!this.bisaProses) return;
            this.galat = '';
            this.uangDiterima = '';
            this.noReferensi = '';
            this.metode = 'tunai';
            this.modal = 'bayar';
            this.$nextTick(() => this.$refs.uang && this.$refs.uang.focus());
        },

        async konfirmasi() {
            if (this.memproses || !this.cukup) return;

            this.memproses = true;
            this.galat = '';

            try {
                const res = await fetch(this.konfig.urlSimpan, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.konfig.csrf,
                    },
                    body: JSON.stringify({
                        tipe_pesanan: this.tipe,
                        nama_customer: this.customer.trim() || null,
                        metode: this.metode,
                        jumlah_bayar: this.metode === 'tunai' ? Number(this.uangDiterima) : this.total,
                        no_referensi: this.metode === 'qris' ? (this.noReferensi.trim() || null) : null,
                        item: this.keranjang.map(i => ({
                            menu_id: i.menu_id,
                            jumlah: i.jumlah,
                            temperatur: i.temperatur,
                            ukuran: i.ukuran,
                            gula: i.gula,
                            catatan: i.catatan || null,
                        })),
                    }),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    if (res.status === 419) {
                        this.galat = 'Sesi berakhir. Muat ulang halaman, lalu coba lagi.';
                    } else if (data.errors) {
                        this.galat = Object.values(data.errors)[0][0];
                    } else {
                        this.galat = data.message || 'Transaksi gagal diproses.';
                    }
                    return;
                }

                this.hasil = data;
                this.modal = 'sukses';
            } catch (e) {
                this.galat = 'Tidak bisa terhubung ke server. Periksa koneksi lalu coba lagi.';
            } finally {
                this.memproses = false;
            }
        },

        cetak() {
            if (this.hasil) window.open(this.hasil.struk_url + '?cetak=1', '_blank');
        },
        pesananBaru() {
            // Muat ulang supaya stok dan nomor order terbaru tampil
            window.location.reload();
        },
    }));
});
</script>
@endpush