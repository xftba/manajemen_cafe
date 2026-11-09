<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, function ($q, $v) {
                $q->where(fn ($w) => $w->where('nama', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%"));
            })
            ->when($request->role, fn ($q, $v) => $q->where('role', $v))
            ->when($request->status === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->orderBy('role')
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = [
            'total'    => User::count(),
            'aktif'    => User::where('is_active', true)->count(),
            'nonaktif' => User::where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'ringkasan'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['role' => 'kasir', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'role'     => ['required', Rule::in(array_keys(User::ROLE))],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], $this->pesan());

        $user = User::create([
            'nama'      => trim($data['nama']),
            'email'     => Str::lower(trim($data['email'])),
            'role'      => $data['role'],
            'password'  => $data['password'],   // di-hash otomatis oleh cast
            'is_active' => true,
        ]);

        AktivitasService::catat('tambah', 'pengguna', "Menambah akun {$user->nama} ({$user->role_label})");

        return redirect()->route('users.index')->with('sukses', "Akun {$user->nama} berhasil ditambahkan.");
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nama'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role'  => ['required', Rule::in(array_keys(User::ROLE))],
        ], $this->pesan());

        // Admin tidak bisa mengubah peran akunnya sendiri
        if ($user->is($request->user())) {
            $data['role'] = $user->role;
        }

        $data['nama']  = trim($data['nama']);
        $data['email'] = Str::lower(trim($data['email']));

        $roleLama = $user->role_label;

        try {
            DB::transaction(function () use ($user, $data) {
                // Admin aktif tidak boleh diturunkan kalau dia satu-satunya admin aktif
                if ($user->role === 'admin' && $user->is_active && $data['role'] !== 'admin') {
                    $this->pastikanAdaAdminLain($user);
                }

                $user->update($data);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $keterangan = "Mengubah akun {$user->nama}";
        if ($roleLama !== $user->role_label) {
            $keterangan .= " (peran {$roleLama} menjadi {$user->role_label})";
        }
        AktivitasService::catat('ubah', 'pengguna', $keterangan);

        return redirect()->route('users.index')->with('sukses', "Akun {$user->nama} berhasil diubah.");
    }

    // Nonaktifkan atau aktifkan kembali (fitur 6)
    public function toggle(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        try {
            DB::transaction(function () use ($user) {
                if ($user->is_active && $user->role === 'admin') {
                    $this->pastikanAdaAdminLain($user);
                }

                $user->update(['is_active' => ! $user->is_active]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($user->is_active) {
            AktivitasService::catat('aktifkan', 'pengguna', "Mengaktifkan kembali akun {$user->nama}");

            return back()->with('sukses', "Akun {$user->nama} diaktifkan kembali.");
        }

        $this->putusSesi($user);
        AktivitasService::catat('nonaktifkan', 'pengguna', "Menonaktifkan akun {$user->nama}");

        return back()->with('sukses', "Akun {$user->nama} dinonaktifkan dan sesi loginnya diakhiri.");
    }

    // Reset password pengguna (fitur 7)
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validateWithBag('reset', [
            'password_baru' => ['required', 'string', 'min:8', 'max:72'],
        ], $this->pesan());

        $user->update(['password' => $data['password_baru']]);   // di-hash otomatis oleh cast

        // Sesi lama pengguna itu diakhiri. Sesi admin sendiri tetap berjalan.
        if (! $user->is($request->user())) {
            $this->putusSesi($user);
        }

        AktivitasService::catat('reset password', 'pengguna', "Mereset password akun {$user->nama}");

        return back()->with('sukses', "Password {$user->nama} berhasil direset. Sampaikan password baru kepada yang bersangkutan.");
    }

    // Pastikan masih ada admin aktif lain. Semua baris admin aktif dikunci dengan urutan yang sama,
    // jadi dua admin yang saling menonaktifkan bersamaan diproses bergantian.
    private function pastikanAdaAdminLain(User $user): void
    {
        $aktif = User::where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');

        if ($aktif->reject(fn ($id) => $id === $user->id)->isEmpty()) {
            throw new \RuntimeException('Tidak dapat dilakukan karena ini satu-satunya admin aktif.');
        }
    }

    // Akhiri semua sesi login milik pengguna: token "ingat saya" diganti dan sesi di database dihapus
    private function putusSesi(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    private function pesan(): array
    {
        return [
            'nama.required'           => 'Nama wajib diisi.',
            'email.required'          => 'Email wajib diisi.',
            'email.email'             => 'Format email tidak valid.',
            'email.unique'            => 'Email ini sudah dipakai akun lain.',
            'role.required'           => 'Pilih peran akun.',
            'role.in'                 => 'Peran yang dipilih tidak valid.',
            'password.required'       => 'Password wajib diisi.',
            'password.min'            => 'Password minimal 8 karakter.',
            'password.max'            => 'Password maksimal 72 karakter.',
            'password.confirmed'      => 'Konfirmasi password tidak sama.',
            'password_baru.required'  => 'Password baru wajib diisi.',
            'password_baru.min'       => 'Password baru minimal 8 karakter.',
            'password_baru.max'       => 'Password baru maksimal 72 karakter.',
        ];
    }
}