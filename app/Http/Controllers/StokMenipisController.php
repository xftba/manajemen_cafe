<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;

class StokMenipisController extends Controller
{
    public function index()
    {
        $bahan = BahanBaku::withCount('resep')
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->orderByRaw('(stok - stok_minimum) asc')
            ->get();

        $ringkasan = [
            'menipis' => $bahan->count(),
            'habis'   => $bahan->filter(fn ($b) => (float) $b->stok <= 0)->count(),
            'total'   => BahanBaku::count(),
        ];

        return view('stok-menipis.index', compact('bahan', 'ringkasan'));
    }
}