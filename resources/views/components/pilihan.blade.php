@props([
    'name',
    'options',
    'selected' => '',
    'semua' => 'Semua',        // isi null untuk menyembunyikan opsi "Semua" (mode form)
    'placeholder' => 'Pilih',
    'auto' => true,            // true = kirim form otomatis saat memilih (untuk filter)
])

<div x-data="{
        buka: false,
        nilai: @js((string) $selected),
        opsi: @js($options),
        kosong: @js($semua ?? $placeholder),
        auto: @js($auto),
        get label() { return this.nilai === '' ? this.kosong : (this.opsi[this.nilai] ?? this.kosong) },
        toggle(el) {
            this.buka = !this.buka;
            if (this.buka && this.auto) {
                const r = el.getBoundingClientRect();
                this.$nextTick(() => {
                    this.$refs.daftar.style.top = (r.bottom + 6) + 'px';
                    this.$refs.daftar.style.left = r.left + 'px';
                });
            }
        },
        pilih(v) {
            this.nilai = v;
            this.buka = false;
            if (this.auto) this.$nextTick(() => this.$refs.input.form.submit());
        },
     }"
     @click.outside="buka = false"
     @keydown.escape.window="buka = false"
     @scroll.window.capture="if (buka && auto && !$refs.daftar.contains($event.target)) buka = false"
     class="relative shrink-0 {{ $auto ? '' : 'w-full' }}">

    <input type="hidden" name="{{ $name }}" x-ref="input" :value="nilai">

    {{-- Tombol --}}
    @if ($auto)
        <button type="button" @click="toggle($el)"
                :class="nilai !== '' ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-600 hover:bg-neutral-300/70'"
                class="flex h-9 items-center gap-2 whitespace-nowrap rounded-full pl-4 pr-3 text-[11px] font-medium transition">
            <span x-text="label"></span>
            <svg class="h-3.5 w-3.5 transition-transform" :class="buka ? 'rotate-180' : ''"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    @else
        <button type="button" @click="toggle($el)"
                :class="buka ? 'border-accent ring-1 ring-accent' : 'border-neutral-300'"
                class="mt-1 flex h-9 w-full items-center justify-between rounded-lg border bg-white px-3 text-left text-[11px] transition">
            <span x-text="label" :class="nilai === '' ? 'text-neutral-400' : 'text-neutral-900'"></span>
            <svg class="h-3.5 w-3.5 text-neutral-500 transition-transform" :class="buka ? 'rotate-180' : ''"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    @endif

    {{-- Daftar pilihan --}}
    <div x-ref="daftar" x-show="buka" x-cloak x-transition.origin.top
         class="{{ $auto ? 'fixed min-w-[160px] max-h-64 overflow-y-auto' : 'absolute left-0 top-full mt-1.5 w-full max-h-64 overflow-y-auto' }}
                z-40 rounded-xl border border-neutral-200 bg-white p-1 shadow-xl">

        @if ($semua !== null)
            <button type="button" @click="pilih('')"
                    :class="nilai === '' ? 'bg-cream font-semibold text-accent' : 'text-neutral-700 hover:bg-cream/60'"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[11px] transition">
                <span>{{ $semua }}</span>
                <svg x-show="nilai === ''" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </button>
        @endif

        @foreach ($options as $kode => $label)
            <button type="button" @click="pilih(@js((string) $kode))"
                    :class="nilai === @js((string) $kode) ? 'bg-cream font-semibold text-accent' : 'text-neutral-700 hover:bg-cream/60'"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[11px] transition">
                <span>{{ $label }}</span>
                <svg x-show="nilai === @js((string) $kode)" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>