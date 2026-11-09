<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Menu;
use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menu = Menu::with(['kategori', 'resep.bahanBaku'])
            ->when($request->q, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->when($request->kategori, fn ($q, $v) => $q->where('kategori_id', $v))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $kategori = Kategori::orderBy('nama')->get();

        $semua   = Menu::with(['kategori', 'resep.bahanBaku'])->get()->map->ringkasan();
        $ringkas = [
            'total'    => $semua->count(),
            'tersedia' => $semua->where('tersedia', true)->count(),
        ];

        return view('menu.index', compact('menu', 'kategori', 'ringkas'));
    }

    public function create()
    {
        return view('menu.form', [
            'menu'     => new Menu([
                'is_active'       => true,
                'pakai_varian'    => true,
                'tambahan_medium' => 0,
                'tambahan_large'  => 0,
            ]),
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        } else {
            unset($data['gambar']);
        }

        $menu = Menu::create($data);
        AktivitasService::catat('tambah', 'menu', "Menambah menu {$menu->nama}");

        return redirect()->route('menu.index')->with('sukses', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        return view('menu.form', [
            'menu'     => $menu,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $this->validasi($request, $menu);

        if ($request->hasFile('gambar')) {
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);   // hapus foto lama
            }
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        } else {
            unset($data['gambar']);
        }

        $menu->update($data);
        AktivitasService::catat('ubah', 'menu', "Mengubah menu {$menu->nama}");

        return redirect()->route('menu.index')->with('sukses', 'Menu berhasil diubah.');
    }

    public function destroy(Menu $menu)
    {
        // Menu yang pernah terjual tidak boleh dihapus, supaya riwayat transaksi tetap utuh
        if (DB::table('detail_transaksi')->where('menu_id', $menu->id)->exists()) {
            return back()->with('error', 'Menu tidak bisa dihapus karena sudah pernah ada di transaksi. Ubah statusnya menjadi Habis.');
        }

        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }

        $nama = $menu->nama;
        $menu->delete();
        AktivitasService::catat('hapus', 'menu', "Menghapus menu {$nama}");

        return back()->with('sukses', 'Menu berhasil dihapus.');
    }

    private function validasi(Request $request, ?Menu $menu = null): array
    {
        $data = $request->validate([
            'kategori_id'     => ['required', 'exists:kategori,id'],
            'nama'            => ['required', 'string', 'max:100', Rule::unique('menu', 'nama')->ignore($menu?->id)],
            'deskripsi'       => ['nullable', 'string', 'max:70'],
            'harga'           => ['required', 'numeric', 'min:0', 'max:99999999'],
            'tambahan_medium' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'tambahan_large'  => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'gambar'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['pakai_varian']    = $request->boolean('pakai_varian');
        $data['tambahan_medium'] = $data['tambahan_medium'] ?? 0;
        $data['tambahan_large']  = $data['tambahan_large'] ?? 0;

        return $data;
    }

    public function toggle(Request $request, Menu $menu)
    {
        return app(KetersediaanController::class)->toggle($request, $menu);
    }

    public function status(Request $request)
    {
        return app(KetersediaanController::class)->status($request);
    }
    
}