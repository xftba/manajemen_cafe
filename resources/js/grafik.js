import Chart from 'chart.js/auto';

Chart.defaults.font.family = "'Poppins', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#737373';

const WARNA = ['#2B3D8F', '#8A5A36', '#D8B778', '#6B7BC4', '#B98A62', '#9CA3AF'];

const rupiah  = (n) => 'Rp ' + Math.round(Number(n)).toLocaleString('id-ID');
const angka   = (n) => Number(n).toLocaleString('id-ID');
const singkat = (v) => v >= 1000000 ? (v / 1000000) + ' jt' : (v >= 1000 ? (v / 1000) + ' rb' : v);

function skala(pakaiRupiah, horizontal) {
    const nilai = {
        beginAtZero: true,
        ticks: { callback: (v) => pakaiRupiah ? singkat(v) : v, precision: 0 },
    };
    const kategori = { grid: { display: false } };

    return horizontal ? { x: nilai, y: kategori } : { x: kategori, y: nilai };
}

function opsi(pakaiRupiah, horizontal = false) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: horizontal ? 'y' : 'x',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (c) => {
                        const v = horizontal ? c.parsed.x : c.parsed.y;
                        return ' ' + (pakaiRupiah ? rupiah(v) : angka(v));
                    },
                },
            },
        },
        scales: skala(pakaiRupiah, horizontal),
    };
}

// Kalau elemen canvas tidak ada di halaman (misalnya data kosong), tidak terjadi apa-apa
function buat(id, config) {
    const el = document.getElementById(id);
    if (!el) return null;
    return new Chart(el, config);
}

window.grafikGaris = (id, labels, data, pakaiRupiah = true) => buat(id, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            data,
            borderColor: WARNA[0],
            backgroundColor: 'rgba(43,61,143,0.10)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: WARNA[0],
        }],
    },
    options: opsi(pakaiRupiah),
});

window.grafikBatang = (id, labels, data, pakaiRupiah = true, horizontal = false) => buat(id, {
    type: 'bar',
    data: {
        labels,
        datasets: [{ data, backgroundColor: WARNA[0], borderRadius: 6, maxBarThickness: 36 }],
    },
    options: opsi(pakaiRupiah, horizontal),
});

window.grafikDonat = (id, labels, data, pakaiRupiah = true) => buat(id, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: WARNA, borderWidth: 0 }] },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } },
            tooltip: {
                callbacks: {
                    label: (c) => ' ' + c.label + ': ' + (pakaiRupiah ? rupiah(c.parsed) : angka(c.parsed)),
                },
            },
        },
    },
});