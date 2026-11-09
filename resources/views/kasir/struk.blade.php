<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaksi->no_invoice }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f3f3; font-family: 'Courier New', monospace; font-size: 12px; color: #000; }
        .struk { width: 302px; margin: 16px auto; background: #fff; padding: 14px 12px; }
        .tengah { text-align: center; }
        .logo { max-height: 48px; margin-bottom: 4px; }
        .judul { font-size: 15px; font-weight: bold; }
        .garis { border-top: 1px dashed #000; margin: 8px 0; }
        .baris { display: flex; justify-content: space-between; gap: 8px; }
        .tebal { font-weight: bold; }
        .kecil { font-size: 11px; color: #444; }
        .item { margin-bottom: 6px; }
        .tombol { display: block; width: 302px; margin: 0 auto 16px; padding: 10px; border: 0; border-radius: 8px;
                  background: #8A5A36; color: #fff; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .struk { margin: 0; width: 100%; padding: 0; }
            .tombol { display: none; }
            @page { margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="struk">
        <div class="tengah">
            @if ($cafe?->logo)
                <img class="logo" src="{{ asset('storage/' . $cafe->logo) }}" alt="Logo">
            @endif
            <div class="judul">{{ $cafe?->nama_cafe ?? config('app.name') }}</div>
            @if ($cafe?->alamat) <div class="kecil">{{ $cafe->alamat }}</div> @endif
            @if ($cafe?->telepon) <div class="kecil">Telp. {{ $cafe->telepon }}</div> @endif
        </div>

        <div class="garis"></div>

        <div class="baris"><span>No</span><span>{{ $transaksi->no_invoice }}</span></div>
        <div class="baris"><span>Tanggal</span><span>{{ $transaksi->tanggal_transaksi->format('d/m/Y H:i') }}</span></div>
        <div class="baris"><span>Kasir</span><span>{{ $transaksi->user->nama }}</span></div>
        <div class="baris"><span>Tipe</span><span>{{ $transaksi->tipe_pesanan === 'dine_in' ? 'Dine In' : 'Takeaway' }}</span></div>
        @if ($transaksi->nama_customer)
            <div class="baris"><span>Customer</span><span>{{ $transaksi->nama_customer }}</span></div>
        @endif

        <div class="garis"></div>

        @foreach ($transaksi->detail as $d)
            <div class="item">
                <div>{{ $d->menu->nama }}</div>
                @if ($d->varian) <div class="kecil">{{ $d->varian }}</div> @endif
                @if ($d->catatan) <div class="kecil">* {{ $d->catatan }}</div> @endif
                <div class="baris">
                    <span>{{ $d->jumlah }} x {{ number_format($d->harga, 0, ',', '.') }}</span>
                    <span>{{ number_format($d->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="garis"></div>

        <div class="baris"><span>Subtotal</span><span>{{ number_format($transaksi->subtotal, 0, ',', '.') }}</span></div>
        @if ($transaksi->pajak > 0)
            <div class="baris"><span>Pajak</span><span>{{ number_format($transaksi->pajak, 0, ',', '.') }}</span></div>
        @endif
        <div class="baris tebal"><span>TOTAL</span><span>{{ number_format($transaksi->total, 0, ',', '.') }}</span></div>

        <div class="garis"></div>

        @if ($pembayaran)
            <div class="baris"><span>Bayar ({{ $pembayaran->metode === 'qris' ? 'QRIS' : 'Tunai' }})</span><span>{{ number_format($pembayaran->jumlah_bayar, 0, ',', '.') }}</span></div>
            <div class="baris"><span>Kembalian</span><span>{{ number_format($pembayaran->kembalian, 0, ',', '.') }}</span></div>
            @if ($pembayaran->no_referensi)
                <div class="baris kecil"><span>Ref</span><span>{{ $pembayaran->no_referensi }}</span></div>
            @endif
        @endif

        @if ($transaksi->status === 'batal')
            <div class="garis"></div>
            <div class="tengah tebal">*** TRANSAKSI DIBATALKAN ***</div>
        @endif

        <div class="garis"></div>
        <div class="tengah">Terima kasih<br>Selamat menikmati</div>
    </div>

    <button class="tombol" onclick="window.print()">Cetak Struk</button>

    @if (request()->boolean('cetak'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>