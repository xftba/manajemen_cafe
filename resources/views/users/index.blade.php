@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')

@section('content')
@php
    $warnaRole = [
        'admin'        => 'bg-accent text-white',
        'kasir'        => 'bg-cream text-accent',
        'staff_gudang' => 'bg-green-100 text-green-800',
    ];

    $ikonTotal    = '<circle cx="9" cy="8" r="3.5"/><path d="M2 20v-1a6 6 0 016-6h2a6 6 0 016 6v1"/><path d="M16 4.5a3.5 3.5 0 010 7M22 20v-1a6 6 0 00-4-5.6"/>';
    $ikonAktif    = '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>';
    $ikonNonaktif = '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>';

    $kartu = [
        ['Total akun', $ringkasan['total'],    $ikonTotal,    'bg-cream text-accent',           'text-neutral-900'],
        ['Aktif',      $ringkasan['aktif'],    $ikonAktif,    'bg-green-100 text-green-700',    'text-green-700'],
        ['Nonaktif',   $ringkasan['nonaktif'], $ikonNonaktif, 'bg-neutral-200 text-neutral-500', 'text-neutral-500'],
    ];

    $kelasInput = 'mt-1 h-9 w-full rounded-lg border border-neutral-300 bg-white px-3 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';

    $editId = old('_edit_id') ?: old('_reset_id');

    $daftarUser = $users->getCollection()->map(fn ($u) => [
        'id'        => $u->id,
        'nama'      => $u->nama,
        'email'     => $u->email,
        'role'      => $u->role,
        'is_active' => (bool) $u->is_active,
        'saya'      => $u->is(auth()->user()),
        'urlUpdate' => route('users.update', $u),
        'urlReset'  => route('users.reset-password', $u),
        'urlToggle' => route('users.toggle', $u),
    ])->values();

    $lamaEdit = old('_edit_id')
        ? ['nama' => old('nama'), 'email' => old('email'), 'role' => old('role')]
        : null;

    // Supaya galat dan isian form Edit tidak muncul di form Tambah Akun
    $lama  = fn ($k) => $editId ? null : old($k);
    $galat = fn ($k) => $editId ? null : $errors->first($k);
@endphp



<div class="mx-auto max-w-[1400px]"
     x-data="editAkun(@js($daftarUser), @js($editId ? (int) $editId : null), @js($lamaEdit))"
     @keydown.escape.window="tutup()">


    <div class="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_300px]">

        {{-- ================= KIRI: DAFTAR PENGGUNA ================= --}}
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

            {{-- Pencarian dan filter (otomatis, tanpa tombol) --}}
            <form method="GET" action="{{ route('users.index') }}"
                  x-data
                  class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari nama atau email..." autocomplete="off"
                           x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                           @input.debounce.500ms="$el.form.submit()"
                           class="h-9 w-full rounded-full border border-neutral-300 bg-white pl-10 pr-4 text-[11px] placeholder:text-neutral-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <x-pilihan name="role"
                        :options="\App\Models\User::ROLE"
                        :selected="request('role')"
                        semua="Semua peran" />

                <x-pilihan name="status"
                :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                :selected="request('status')"
                semua="Semua status" />
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead class="bg-cream text-left text-accent">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Pengguna</th>
                                <th class="px-4 py-2.5 font-semibold">Peran</th>
                                <th class="px-4 py-2.5 font-semibold">Status</th>
                                <th class="px-4 py-2.5 font-semibold">Dibuat</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                @php $saya = $u->is(auth()->user()); @endphp
                                <tr class="border-t border-neutral-100 transition hover:bg-cream/40">

                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold
                                                         {{ $u->is_active ? 'bg-accent text-white' : 'bg-neutral-200 text-neutral-500' }}">
                                                {{ strtoupper(substr($u->nama, 0, 1)) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="flex items-center gap-1.5 text-[12px] font-semibold {{ $u->is_active ? '' : 'text-neutral-400' }}">
                                                    <span class="truncate">{{ $u->nama }}</span>
                                                    @if ($saya)
                                                        <span class="rounded-full bg-neutral-200 px-1.5 py-px text-[9px] font-semibold text-neutral-600">Anda</span>
                                                    @endif
                                                </p>
                                                <p class="truncate text-[10px] text-neutral-400">{{ $u->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-2">
                                        <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $warnaRole[$u->role] ?? 'bg-neutral-100 text-neutral-700' }}">
                                            {{ $u->role_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold {{ $u->is_active ? 'text-green-700' : 'text-neutral-500' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $u->is_active ? 'bg-green-600' : 'bg-neutral-400' }}"></span>
                                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ $u->created_at->format('d/m/Y') }}</td>

                                    <td class="px-4 py-2">
                                        <div class="flex items-center justify-end gap-1.5">

                                            {{-- Ubah --}}
                                            <a href="{{ route('users.edit', $u) }}" @click.prevent="buka({{ $u->id }})"
                                            title="Ubah akun" aria-label="Ubah akun {{ $u->nama }}"
                                            class="flex h-7 w-7 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-accent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                                </svg>
                                            </a>

                                            {{-- Aktifkan / Nonaktifkan --}}
                                            @unless ($saya)
                                                <form method="POST" action="{{ route('users.toggle', $u) }}"
                                                    onsubmit="return confirm(@js(($u->is_active ? 'Nonaktifkan akun ' : 'Aktifkan kembali akun ') . $u->nama . '?'))">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ $u->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                                            aria-label="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $u->nama }}"
                                                            class="flex h-7 w-7 items-center justify-center rounded-full transition
                                                                {{ $u->is_active ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            @if ($u->is_active)
                                                                <circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>
                                                            @else
                                                                <circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>
                                                            @endif
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-8 w-8 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $ikonTotal !!}</svg>
                                        <p class="mt-2 text-xs text-neutral-400">Tidak ada akun yang cocok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $users->links() }}</div>
        </section>

        {{-- ================= KANAN: FORM TAMBAH AKUN ================= --}}
        <aside class="rounded-2xl bg-white p-4 shadow-sm xl:sticky xl:top-4">

            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10" cy="8" r="3.5"/><path d="M3 20v-1a6 6 0 016-6h2a6 6 0 016 6v1M19 8v6M16 11h6"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-[13px] font-semibold leading-tight">Tambah Akun</h2>
                    <p class="text-[10px] text-neutral-500">Buat akun pengguna baru.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('users.store') }}" x-data="kataSandi()"
                  class="mt-3 space-y-2.5 border-t border-neutral-200 pt-3">
                @csrf

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Nama</label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required maxlength="100"
                           placeholder="Nama lengkap" class="{{ $kelasInput }}">
                    @error('nama') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Email <span class="font-normal text-neutral-400">(untuk login)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="150"
                           placeholder="nama@email.com" class="{{ $kelasInput }}">
                    @error('email') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-[11px] font-medium text-neutral-700">Peran</label>
                    <x-pilihan name="role"
                            :options="\App\Models\User::ROLE"
                            :selected="old('role')"
                            :semua="null"
                            placeholder="Pilih peran"
                            :auto="false" />
                    @error('role') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Password awal --}}
                <div class="rounded-xl bg-cream/60 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-medium text-neutral-700">Password awal</p>
                        <button type="button" @click="acak()"
                                class="text-[10px] font-semibold text-accent hover:underline">Buat otomatis</button>
                    </div>

                    <input :type="lihat ? 'text' : 'password'" name="password" x-model="nilai" required
                           minlength="8" maxlength="72" autocomplete="new-password"
                           placeholder="Minimal 8 karakter" class="{{ $kelasInput }}">
                    @error('password') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror

                    <input :type="lihat ? 'text' : 'password'" name="password_confirmation" x-model="ulang" required
                           autocomplete="new-password" placeholder="Ulangi password" class="{{ $kelasInput }}">

                    <label class="mt-2 flex items-center gap-1.5 text-[10px] text-neutral-600">
                        <input type="checkbox" x-model="lihat" class="h-3 w-3 rounded accent-[var(--color-accent,#8b5a3c)]">
                        Tampilkan password
                    </label>
                </div>

                <p class="text-[10px] leading-snug text-neutral-500">
                    Catat password ini dan sampaikan kepada pemilik akun. Setelah disimpan, password tidak bisa dilihat lagi.
                </p>

                <button type="submit"
                        class="h-9 w-full rounded-full bg-accent text-[12px] font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.99]">
                    Simpan Akun
                </button>
            </form>
        </aside>
    </div>

{{-- ================= POPUP UBAH AKUN ================= --}}
<div x-show="id !== null" x-cloak @click.self="tutup()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">

    <template x-if="u">
        <div class="max-h-[90vh] w-full max-w-[340px] overflow-y-auto rounded-2xl bg-white p-4 shadow-xl">

            {{-- Informasi akun --}}
            <div class="flex items-center gap-3 border-b border-neutral-200 pb-3">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-accent">
                    <span class="text-2xl font-semibold text-white" x-text="u.nama.charAt(0).toUpperCase()"></span>
                </div>

                <div class="min-w-0">
                    <h3 class="truncate text-[13px] font-semibold" x-text="u.nama"></h3>
                    <p class="mt-0.5 truncate text-[11px] text-neutral-500" x-text="u.email"></p>
                    <p class="mt-1 flex items-center gap-1.5 text-[11px] font-medium"
                       :class="u.is_active ? 'text-green-700' : 'text-neutral-500'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="u.is_active ? 'bg-green-600' : 'bg-neutral-400'"></span>
                        <span x-text="u.is_active ? 'Akun aktif' : 'Akun nonaktif'"></span>
                    </p>
                </div>
            </div>

            {{-- ===== Data akun (tombol simpan ada di bawah, terhubung lewat atribut form) ===== --}}
            <form id="form-ubah-akun" method="POST" :action="u.urlUpdate">
                @csrf
                @method('PUT')
                <input type="hidden" name="_edit_id" :value="u.id">

                {{-- Nama --}}
                <label class="mt-3 block text-[11px] font-semibold">Nama</label>
                <input type="text" name="nama" x-model="f.nama" required maxlength="100"
                       class="mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">
                @if ($errors->has('nama') && old('_edit_id'))
                    <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('nama') }}</p>
                @endif

                {{-- Email --}}
                <label class="mt-3 block text-[11px] font-semibold">Email</label>
                <input type="email" name="email" x-model="f.email" required maxlength="150"
                       class="mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">
                @if ($errors->has('email') && old('_edit_id'))
                    <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('email') }}</p>
                @endif

                {{-- Peran --}}
                <p class="mt-3 text-[11px] font-semibold">Peran</p>
                <input type="hidden" name="role" :value="f.role" :disabled="u.saya">
                <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                    @foreach (\App\Models\User::ROLE as $kode => $label)
                        <button type="button" :disabled="u.saya" @click="f.role = @js($kode)"
                                :class="f.role === @js($kode)
                                    ? 'border-accent bg-white text-neutral-900'
                                    : 'border-transparent bg-surface text-neutral-400'"
                                class="flex h-9 items-center justify-center rounded-lg border text-[11px] font-medium transition disabled:cursor-not-allowed disabled:opacity-40">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <p x-show="u.saya" class="mt-1 text-[10px] text-neutral-500">Anda tidak bisa mengubah peran akun sendiri.</p>
                @if ($errors->has('role') && old('_edit_id'))
                    <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->first('role') }}</p>
                @endif
            </form>

            {{-- ===== Reset password ===== --}}
            <form method="POST" :action="u.urlReset" x-data="kataSandi()"
                  @submit="if (! confirm('Reset password untuk ' + u.nama + '?')) $event.preventDefault()"
                  class="mt-4 border-t border-neutral-200 pt-3">
                @csrf
                <input type="hidden" name="_reset_id" :value="u.id">

                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold">Password baru</p>
                    <button type="button" @click="acak()" class="text-[11px] font-medium text-accent hover:underline">Buat otomatis</button>
                </div>

                <input :type="lihat ? 'text' : 'password'" name="password_baru" x-model="nilai" required
                       minlength="8" maxlength="72" autocomplete="new-password"
                       placeholder="Minimal 8 karakter"
                       class="mt-1.5 h-9 w-full rounded-lg border-0 bg-surface px-3 text-[11px] placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-accent">
                @if ($errors->getBag('reset')->has('password_baru'))
                    <p x-show="id === bukaId" class="mt-1 text-[10px] text-red-600">{{ $errors->getBag('reset')->first('password_baru') }}</p>
                @endif

                <label class="mt-2 flex items-center gap-1.5 text-[11px] text-neutral-600">
                    <input type="checkbox" x-model="lihat" class="h-3.5 w-3.5 rounded"> Tampilkan password
                </label>
                <p class="mt-1.5 text-[10px] leading-snug text-neutral-500">
                    Sampaikan password baru ke pemilik akun. Sesi login lamanya akan diakhiri.
                </p>

                <button type="submit"
                        class="mt-2 h-9 w-full rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                    Reset Password
                </button>
            </form>

            {{-- ===== Tombol aksi ===== --}}
            <div class="mt-4 grid grid-cols-[80px_1fr] gap-2 border-t border-neutral-200 pt-3">
                <button type="button" @click="tutup()"
                        class="h-9 rounded-lg border border-accent text-[11px] font-semibold text-accent transition hover:bg-accent/5">
                    Batal
                </button>
                <button type="submit" form="form-ubah-akun"
                        class="h-9 rounded-lg bg-accent text-[11px] font-semibold text-white transition hover:opacity-90">
                    Simpan perubahan
                </button>
            </div>
        </div>
    </template>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('kataSandi', () => ({
        lihat: false,
        nilai: '',
        ulang: '',

        // Password acak 10 karakter, tanpa huruf dan angka yang mudah tertukar (0, O, 1, l, I)
        acak() {
            const huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
            const acak = new Uint32Array(10);
            crypto.getRandomValues(acak);

            this.nilai = Array.from(acak, n => huruf[n % huruf.length]).join('');
            this.ulang = this.nilai;
            this.lihat = true;
        },
    }));

    Alpine.data('editAkun', (daftar, bukaId, lama) => ({
        daftar,
        bukaId,
        id: null,
        f: { nama: '', email: '', role: '' },

        get u() { return this.daftar.find(x => x.id === this.id) || null; },

        buka(id) {
            const u = this.daftar.find(x => x.id === id);
            if (!u) return;
            this.f = { nama: u.nama, email: u.email, role: u.role };
            this.id = id;
        },
        tutup() { this.id = null; },

        init() {
            if (!this.bukaId) return;
            this.buka(this.bukaId);
            // Kalau validasi gagal, pakai isian terakhir pengguna
            if (lama) {
                for (const k of ['nama', 'email', 'role']) {
                    if (lama[k] !== null && lama[k] !== undefined) this.f[k] = lama[k];
                }
            }
        },
    }));
});
</script>
@endpush