{{--
    <x-denah> — FloorPlan: denah interaktif (tampilan grid/kartu, sesuai desain terbaru).
    Bentuk & posisi denah BERASAL dari config/denah-layout.php dan TIDAK diubah di sini —
    file ini hanya menyambungkan tampilan tersebut ke data fasilitas & status ketersediaan
    yang sesungguhnya (dari database), supaya tetap bisa diklik untuk memesan/melihat detail.
    Props:
      - lantai              : nomor_lantai ('1','2','3A','3B','5')
      - statusPerFasilitas  : [kode_fasilitas => 'hijau'|'merah'|'kuning'] (dari AvailabilityService)
      - clickable           : true = mode pilih (pemesan, multi-select + bar ringkasan);
                              false = mode lihat saja (admin monitoring, klik = lihat detail siapa pemesannya)
      - linkTemplate        : URL dengan placeholder __ID__ (id_fasilitas)
      - jenis               : id_jenis_sewa terpilih (opsional) — dipakai untuk tooltip harga saja,
                              tidak memengaruhi alur/logic reservasi.
      - kategori            : kategori yang sedang dipesan ('Working Space'/'Co-Working Space'/dst,
                              opsional). Kalau diisi & clickable=true: ruangan berlabel R hanya bisa
                              dipesan lewat kategori Working Space, ruangan berlabel K (Kubikal) hanya
                              lewat Co-Working Space — supaya tidak tertukar kategori saat memesan.
      - title, info         : override judul/info header (opsional, kalau null pakai dari config).

    Status ruangan (3 warna saja, tidak ada abu-abu/nonaktif — ruangan Tidak Aktif tetap
    ditampilkan & tetap masuk hitungan "merah", persis seperti ruangan yang penuh):
      - kosong (hijau)  : tersedia, bisa dipesan.
      - kuning          : sebagian jam/tanggal terisi, tetap bisa dipilih (dicek lagi saat isi jadwal).
      - terisi (merah)  : penuh, atau memang sedang tidak disewakan — tidak bisa dipilih.
--}}
@props([
    'lantai' => '1',
    'statusPerFasilitas' => [],
    'clickable' => false,
    'linkTemplate' => null,
    'jenis' => null,
    'kategori' => null,
    'title' => null,
    'info' => null,
    // Sembunyikan header judul+info internal komponen ini — dipakai saat halaman pemanggil
    // sudah punya kartu header sendiri (mis. monitoring admin), supaya tidak duplikat.
    'hideHeader' => false,
])

@php
    use App\Models\Fasilitas;
    use App\Models\TarifSewa;
    use App\Enums\StatusAktif;

    $data = config("denah-layout.{$lantai}");
    if (! $data) {
        // Fallback: lantai tidak dikenal
        $data = ['title' => "LANTAI {$lantai}", 'info' => 'Denah belum tersedia.', 'rows' => []];
    }

    $displayTitle    = $title ?? $data['title'];
    $displaySubtitle = $data['subtitle'] ?? null;
    $displayInfo     = $info  ?? ($data['info'] ?? '');

    // Data fasilitas sesungguhnya dari database, dikaitkan lewat kode_fasilitas — dipakai untuk
    // id_fasilitas (link), nama, kapasitas, harga (kartu hover). Posisi/bentuk kartu tetap dari config.
    $db = Fasilitas::whereHas('lantai', fn ($q) => $q->where('nomor_lantai', $lantai))
        ->get(['id_fasilitas', 'kode_fasilitas', 'nama_fasilitas', 'kapasitas', 'luas', 'deskripsi', 'status_aktif'])
        ->keyBy('kode_fasilitas');

    $hargaByFasilitas = ($clickable && $jenis)
        ? TarifSewa::whereIn('id_fasilitas', $db->pluck('id_fasilitas'))
            ->where('id_jenis_sewa', $jenis)
            ->where('status_aktif', StatusAktif::Aktif->value)
            ->pluck('harga', 'id_fasilitas')
        : collect();

    // Helper: bangun class + atribut untuk 1 room, dari 1 baris config + data DB terkait.
    $renderRoom = function (array $item) use ($statusPerFasilitas, $clickable, $linkTemplate, $kategori, $db, $hargaByFasilitas) {
        $tipe     = $item['tipe'] ?? 'ruangan';
        $isRuang  = $tipe === 'ruangan';
        $kode     = $item['kode']  ?? null;
        $label    = $item['label'] ?? ($kode ?? '');
        $luas     = $item['luas']  ?? null;
        $shape    = $item['shape'] ?? null;

        // Klasifikasi visual R (Working Space besar) vs K (Kubikal kecil)
        $labelUpper = strtoupper($label);
        $isR = $isRuang && str_starts_with($labelUpper, 'R');
        $isK = $isRuang && str_starts_with($labelUpper, 'K');

        $f = ($isRuang && $kode) ? $db->get($kode) : null;
        $tidakAktif = $f && $f->status_aktif->value === 'Tidak Aktif';

        // Ruangan berlabel R hanya boleh dipesan lewat kategori Working Space, berlabel K
        // hanya lewat Co-Working Space — supaya tidak ada kubikal terpesan sebagai Working
        // Space atau sebaliknya. Hanya berlaku saat memesan (clickable), tidak untuk admin.
        $bukanKategoriIni = $clickable && $kategori && (
            ($kategori === 'Working Space' && $isK)
            || ($kategori === 'Co-Working Space' && $isR)
        );

        // Status ketersediaan sesungguhnya (Menunggu/Disetujui yang mencakup jadwal terpilih).
        $rawStatus = ($isRuang && $kode) ? ($statusPerFasilitas[$kode] ?? 'hijau') : 'hijau';

        // Hanya 3 warna: hijau (kosong), kuning (sebagian terisi), merah (penuh). Ruangan
        // Tidak Aktif & di luar kategori yang sedang dipesan ikut masuk "merah" — tetap
        // tampil & tetap ruangan biasa, bukan kategori/warna terpisah.
        if ($isRuang && ($tidakAktif || $bukanKategoriIni || $rawStatus === 'merah')) {
            $status = 'terisi';
            $statusLabel = $bukanKategoriIni ? 'Bukan '.$kategori : 'Penuh';
        } elseif ($isRuang && $rawStatus === 'kuning') {
            $status = 'kuning';
            $statusLabel = 'Sebagian terisi';
        } else {
            $status = 'kosong';
            $statusLabel = 'Kosong';
        }

        $classes = ['d-room', $tipe];
        if ($shape) $classes[] = $shape;
        if ($isRuang) $classes[] = $status;
        if ($isR) $classes[] = 'is-rroom';
        if ($isK) $classes[] = 'is-kroom';

        // Bisa diklik kalau fasilitasnya nyata + ada tujuan link, dan:
        // - mode admin (clickable=false): tetap bisa diklik walau penuh, untuk melihat detail;
        // - mode pemesan (clickable=true): kosong & kuning bisa dipilih, hanya merah yang tidak.
        $clickableThis = $isRuang && $kode && $f && $linkTemplate && (! $clickable || $status !== 'terisi');
        if ($clickableThis) $classes[] = 'clickable';

        // Flex-grow: pakai `flex` kalau ada, atau `luas` (proporsional dengan luas),
        // fallback 1. Nilai flex-grow tidak boleh 0 supaya kartu tidak menghilang.
        $flexGrow = $item['flex'] ?? $luas ?? 1;

        $href = $clickableThis ? str_replace('__ID__', (string) $f->id_fasilitas, $linkTemplate) : null;
        $harga = $f ? $hargaByFasilitas->get($f->id_fasilitas) : null;
        $hint = $clickable
            ? ($status !== 'terisi' ? 'Klik untuk memilih' : null)
            : ($linkTemplate && $f ? 'Klik untuk lihat detail' : null);

        return [
            'classes' => $classes, 'label' => $label, 'luas' => $luas, 'status' => $status,
            'statusLabel' => $statusLabel, 'clickableThis' => $clickableThis, 'href' => $href,
            'flexGrow' => $flexGrow, 'tipe' => $tipe, 'kode' => $kode, 'f' => $f, 'harga' => $harga, 'hint' => $hint,
        ];
    };

    // Hall (Lantai 5): satu kode mewakili seluruh Convention Hall (bukan per meja/kursi).
    $hallInfo = null;
    if (isset($data['hall'])) {
        $kodeHall = $data['hall']['kode_hall'] ?? null;
        $fHall = $kodeHall ? $db->get($kodeHall) : null;
        $tidakAktifHall = $fHall && $fHall->status_aktif->value === 'Tidak Aktif';
        $rawStatusHall = $kodeHall ? ($statusPerFasilitas[$kodeHall] ?? 'hijau') : 'hijau';

        // Sama seperti ruangan biasa: hanya hijau/kuning/merah, Tidak Aktif ikut masuk merah.
        if ($tidakAktifHall || $rawStatusHall === 'merah') {
            $statusHall = 'terisi';
            $statusLabelHall = 'Penuh';
        } elseif ($rawStatusHall === 'kuning') {
            $statusHall = 'kuning';
            $statusLabelHall = 'Sebagian terisi';
        } else {
            $statusHall = 'kosong';
            $statusLabelHall = 'Kosong';
        }

        $clickableHall = $kodeHall && $fHall && $linkTemplate && (! $clickable || $statusHall !== 'terisi');
        $hargaHall = $fHall ? $hargaByFasilitas->get($fHall->id_fasilitas) : null;
        $hintHall = $clickable
            ? ($statusHall !== 'terisi' ? 'Klik untuk memilih' : null)
            : ($linkTemplate && $fHall ? 'Klik untuk lihat detail' : null);

        $hallInfo = [
            'kode' => $kodeHall,
            'f' => $fHall,
            'status' => $statusHall,
            'statusLabel' => $statusLabelHall,
            'clickableThis' => $clickableHall,
            'href' => $clickableHall ? str_replace('__ID__', (string) $fHall->id_fasilitas, $linkTemplate) : null,
            'harga' => $hargaHall,
            'hint' => $hintHall,
        ];
    }
@endphp

{{-- CSS di-render SEKALI per halaman (walau komponen dipanggil berkali-kali).
     Tidak pakai @push supaya bisa jalan di layout apapun tanpa @stack. --}}
@once
<style>
        :root {
            --dl-green:    #1e8a3d;
            --dl-green-bg: #eafbf0;
            --dl-green-dk: #136127;
            --dl-red:      #d94f3d;
            --dl-red-bg:   #fdecea;
            --dl-yellow:    #c98a1c;
            --dl-yellow-bg: #fff6e0;
            --dl-yellow-dk: #8a5d0e;
            --dl-blue-bg:  #eaf6fc;
            --dl-blue-bd:  #bfe3f5;
            --dl-gray-bg:  #eef0f2;
            --dl-gray-bd:  #dde1e5;
            --dl-corridor: #f7f8fa;
            --dl-ink:      #1c2430;
            --dl-sub:      #6b7480;
        }

        .denah-layout {
            font-family: 'Plus Jakarta Sans', 'DM Sans', 'Segoe UI', sans-serif;
            color: var(--dl-ink);
            background: linear-gradient(180deg, #fbfcfd 0%, #f4f6f8 100%);
            padding: 22px;
            border-radius: 20px;
        }
        .denah-layout * { box-sizing: border-box; }

        /* Header */
        .dl-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
        }
        .dl-title {
            font-weight: 800; font-size: 1.7rem; margin: 0; letter-spacing: -0.02em; color: var(--dl-ink);
        }
        .dl-title small {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: var(--dl-green-dk); letter-spacing: 0.04em; margin-top: 2px;
        }
        .dl-info {
            background: #fff; border: 1px solid var(--dl-gray-bd); border-radius: 12px;
            padding: 10px 16px; font-size: 0.78rem; color: var(--dl-sub);
            box-shadow: 0 1px 3px rgba(20, 30, 40, 0.04);
        }
        .dl-info strong { color: var(--dl-ink); }

        /* Building container */
        .dl-building {
            position: relative; border: 1px solid var(--dl-gray-bd); border-radius: 16px;
            background: #fff; padding: 26px; overflow: hidden;
            box-shadow: 0 8px 24px rgba(20, 30, 45, 0.05);
        }

        /* Rows (flex mode) */
        .dl-row {
            display: flex; width: 100%; gap: 12px; margin-bottom: 12px;
        }
        .dl-row:last-child { margin-bottom: 0; }

        /* Grid mode (lantai 2, 3A, 3B) */
        .dl-grid { display: grid; gap: 12px; margin-bottom: 12px; }
        .dl-grid .d-room { height: 100%; }

        /* Room dasar */
        .d-room {
            position: relative; flex: 1 1 0; min-height: 96px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; border: none; background: #fff; padding: 10px 8px;
            text-align: center; font-family: inherit; border-radius: 10px;
            text-decoration: none; color: inherit;
        }
        .d-room.clickable { cursor: pointer; }

        /* Ruangan bisa dipesan */
        .d-room.ruangan {
            border: 1.5px solid var(--dl-green); background: #fff;
            box-shadow: 0 1px 3px rgba(20, 120, 60, 0.06);
        }
        .d-room.ruangan.terisi {
            border-color: var(--dl-red); box-shadow: 0 1px 3px rgba(180, 60, 45, 0.06);
        }
        .d-room.ruangan.kuning {
            border-color: var(--dl-yellow); box-shadow: 0 1px 3px rgba(160, 110, 20, 0.06);
        }
        .d-room.ruangan.clickable:hover {
            background: var(--dl-green-bg); transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(30, 138, 61, 0.18); transition: 0.18s;
        }
        .d-room.ruangan.terisi.clickable:hover {
            background: var(--dl-red-bg); box-shadow: 0 8px 18px rgba(217, 79, 61, 0.18);
        }
        .d-room.ruangan.kuning.clickable:hover {
            background: var(--dl-yellow-bg); box-shadow: 0 8px 18px rgba(160, 110, 20, 0.18);
        }

        /* R (Working Space) — kartu besar & menonjol */
        .d-room.is-rroom { min-height: 150px; border-width: 2px; padding: 16px 10px; }
        .d-room.is-rroom .d-kode   { font-size: 1.15rem; }
        .d-room.is-rroom .d-luas   { font-size: 0.75rem; }
        .d-room.is-rroom .d-status { font-size: 0.65rem; padding: 3px 12px; }
        .dl-grid .d-room.is-rroom { min-height: 100%; }

        /* K (Kubikal) — kartu kecil */
        .d-room.is-kroom { min-height: 70px; padding: 6px 4px; border-width: 1.2px; }
        .d-room.is-kroom .d-kode   { font-size: 0.78rem; }
        .d-room.is-kroom .d-luas   { font-size: 0.6rem; }
        .d-room.is-kroom .d-status { font-size: 0.55rem; padding: 1px 7px; }

        /* Non-ruangan */
        .d-room.fasilitas { background: var(--dl-blue-bg); border: 1px solid var(--dl-blue-bd); }
        .d-room.servis    { background: var(--dl-gray-bg); border: 1px solid var(--dl-gray-bd); }
        .d-room.tangga {
            background: repeating-linear-gradient(0deg, #e6e9ec, #e6e9ec 4px, #eef0f2 4px, #eef0f2 8px);
            border: 1px solid var(--dl-gray-bd);
        }
        .d-room.koridor {
            background: var(--dl-corridor); flex: 1 1 100%; min-height: 56px;
            font-weight: 600; color: #9aa2ab; border: 1px dashed #d6dade;
            letter-spacing: 0.08em; font-size: 0.78rem;
        }
        .d-room.taman {
            background: #effaef; color: #2f7d32; font-size: 0.68rem;
            border: 1px solid #cdeecb;
        }
        .d-room.blank { background: transparent; border: none !important; }

        /* Ruangan angled (sudut kanan atas dipotong) */
        .d-room.angled { clip-path: polygon(0 0, 85% 0, 100% 25%, 100% 100%, 0 100%); }

        /* Isi kartu */
        .d-kode  { font-weight: 700; font-size: 0.9rem; color: var(--dl-ink); }
        .d-luas  { font-size: 0.66rem; color: var(--dl-sub); }
        .d-label { font-size: 0.7rem; color: #8a9099; font-weight: 600; letter-spacing: 0.03em; }
        .d-status {
            margin-top: 1px; font-size: 0.58rem; font-weight: 700;
            padding: 2px 9px; border-radius: 20px; letter-spacing: 0.04em;
        }
        .d-status.kosong { color: var(--dl-green-dk);  border: 1px solid var(--dl-green);  background: var(--dl-green-bg); }
        .d-status.terisi { color: #a0392b;             border: 1px solid var(--dl-red);    background: var(--dl-red-bg); }
        .d-status.kuning { color: var(--dl-yellow-dk); border: 1px solid var(--dl-yellow); background: var(--dl-yellow-bg); }

        /* Kompas */
        .dl-compass {
            position: absolute; bottom: 14px; right: 16px;
            font-weight: 700; color: #6b7480; background: #fff;
            border: 1px solid var(--dl-gray-bd); border-radius: 50%;
            width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
            font-size: 0.62rem; box-shadow: 0 2px 6px rgba(20, 30, 40, 0.06);
        }

        /* Legenda */
        .dl-legend {
            display: flex; flex-wrap: wrap; gap: 20px; margin-top: 16px;
            padding: 14px 18px; border: 1px solid var(--dl-gray-bd);
            border-radius: 14px; font-size: 0.78rem; background: #fff;
        }
        .dl-legend-item { display: flex; align-items: center; gap: 8px; color: var(--dl-sub); }
        .dl-swatch { width: 16px; height: 16px; border-radius: 5px; display: inline-block; }
        .sw-ruangan   { border: 2px solid var(--dl-green); }
        .sw-kuning    { border: 2px solid var(--dl-yellow); }
        .sw-terisi    { border: 2px solid var(--dl-red); }
        .sw-fasilitas { background: var(--dl-blue-bg); border: 1px solid var(--dl-blue-bd); }
        .sw-servis    { background: var(--dl-gray-bg); border: 1px solid var(--dl-gray-bd); }

        /* Convention Hall (Lantai 5) — kursi/meja HANYA ilustrasi tata letak, netral (bukan
           hijau/merah) karena bukan lagi target pilih satuan; status ada di badge tunggal. */
        .dl-hall { display: flex; flex-direction: column; gap: 24px; padding: 8px; }
        .dl-hall-panggung {
            background: var(--dl-gray-bg); text-align: center; padding: 18px;
            font-weight: 700; border-radius: 12px; letter-spacing: 0.06em; color: #5a6270;
        }
        .dl-hall-rows { display: flex; flex-direction: column; gap: 30px; padding: 14px 0; }
        .dl-hall-row { display: flex; justify-content: space-evenly; gap: 18px; }
        .dl-table {
            width: 92px; height: 92px; border-radius: 50%;
            border: 2px solid var(--dl-gray-bd); background: #fff;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-weight: 700; font-family: inherit; text-decoration: none; color: inherit;
            box-shadow: 0 2px 8px rgba(20, 30, 40, 0.05);
        }
        .dl-table-nomor { font-size: 1.15rem; color: var(--dl-ink); }
        .dl-table-kursi { font-size: 0.6rem; color: var(--dl-sub); font-weight: 500; }
        .dl-hall-footer { display: flex; gap: 12px; }
        .dl-mejapanjang {
            flex: 1; background: var(--dl-gray-bg); text-align: center;
            padding: 10px; font-weight: 600; border-radius: 10px;
            font-size: 0.85rem; color: #5a6270;
        }
        .dl-sound {
            width: 84px; background: var(--dl-gray-bg); display: flex;
            align-items: center; justify-content: center; font-size: 0.6rem;
            text-align: center; border-radius: 10px; color: #8a9099; font-weight: 600;
        }

        /* Layar kecil: JANGAN dipepetkan sampai kartu ruangan jadi terlalu kecil/susah
           dibaca — kartu tetap punya lebar minimum yang layak, dan denah jadi bisa
           digulir ke samping (bukan ditumpuk/wrap) supaya semua ruangan tetap terlihat
           utuh dengan ukuran yang sama seperti tampilan desktop. */
        @media (max-width: 640px) {
            .dl-building { overflow-x: auto; -webkit-overflow-scrolling: touch; padding: 18px 16px; }
            .dl-row { flex-wrap: nowrap; }
            .d-room { min-height: 78px; flex: 0 0 92px; width: 92px; }
            .d-room.is-rroom { min-height: 110px; flex-basis: 132px; width: 132px; }
            .d-room.is-kroom { flex-basis: 68px; width: 68px; }
            .d-room.koridor { flex: 0 0 auto; width: auto; min-width: 320px; }
            .dl-grid { grid-template-columns: none !important; grid-auto-flow: column; grid-auto-columns: 92px; overflow: visible; }
            .dl-table { width: 66px; height: 66px; }
        }
        .dl-scroll-hint { display: none; }
        @media (max-width: 640px) {
            .dl-scroll-hint {
                display: block; text-align: center; margin-top: 10px;
                font-size: .68rem; font-weight: 600; color: var(--dl-sub); letter-spacing: .02em;
            }
        }

        /* ===== Tambahan: umpan balik ruangan terpilih + bar ringkasan (mode pemesan) =====
           Ditambahkan di sini, TIDAK mengubah aturan style denah di atas. */
        .d-room.terpilih { outline: 2.5px solid #2563eb; outline-offset: -1px; background: #eff6ff !important; }
        .d-room.terpilih .d-kode { color: #1d4ed8; }

        /* Lantai 5: seluruh ruangan (bukan per meja/kursi) jadi satu target klik. */
        .dl-hall-click { text-decoration: none; color: inherit; display: block; border-radius: 14px; transition: .18s; }
        .dl-hall-click.clickable { cursor: pointer; }
        .dl-hall-click.clickable:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(20, 120, 60, .14); }
        .dl-hall-click.terisi.clickable:hover { box-shadow: 0 10px 24px rgba(180, 60, 45, .14); }
        .dl-hall-click.kuning.clickable:hover { box-shadow: 0 10px 24px rgba(160, 110, 20, .14); }
        .dl-hall-click.terpilih { outline: 2.5px solid #2563eb; outline-offset: 3px; }
        .dl-hall-status {
            display: inline-block; margin-bottom: 12px; font-size: 0.7rem; font-weight: 700;
            padding: 4px 12px; border-radius: 20px; letter-spacing: 0.04em;
        }
        .dl-hall-status.kosong { color: var(--dl-green-dk);  border: 1px solid var(--dl-green);  background: var(--dl-green-bg); }
        .dl-hall-status.terisi { color: #a0392b;             border: 1px solid var(--dl-red);    background: var(--dl-red-bg); }
        .dl-hall-status.kuning { color: var(--dl-yellow-dk); border: 1px solid var(--dl-yellow); background: var(--dl-yellow-bg); }

        .dl-select-bar {
            display: flex; flex-wrap: wrap; align-items: center; gap: .6rem;
            margin-top: 16px; padding: 14px 18px; border: 1px solid var(--dl-gray-bd);
            border-radius: 14px; background: #fff; box-shadow: 0 1px 3px rgba(20,30,40,.04);
        }
        .dl-select-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
        .dl-select-chips a {
            display: inline-block; padding: .22rem .65rem; border-radius: 2rem;
            background: var(--dl-green-bg); color: var(--dl-green-dk); font-size: .75rem;
            font-weight: 700; text-decoration: none; transition: background .15s;
        }
        .dl-select-chips a:hover { background: #d3f5df; }

        /* Kartu detail saat kursor di atas ruangan (mengambang, mengikuti kursor). */
        .dn-card {
            position: fixed; z-index: 1080; width: max-content; max-width: 16rem;
            background: #0f172a; color: #f1f5f9; border-radius: .85rem; padding: .7rem .85rem;
            font-size: .78rem; font-family: 'DM Sans', sans-serif; box-shadow: 0 14px 30px rgba(2,6,23,.28);
            pointer-events: none; transition: opacity .1s ease;
        }
        .dn-card-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .4rem; }
        .dn-card-kode { font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: .02em; }
        .dn-card-status { font-size: .64rem; font-weight: 700; padding: .15rem .55rem; border-radius: 2rem; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
        .dn-card-status.kosong { background: rgba(34,197,94,.22); color: #86efac; }
        .dn-card-status.terisi { background: rgba(239,68,68,.22); color: #fca5a5; }
        .dn-card-status.kuning { background: rgba(234,179,8,.22); color: #fde68a; }
        .dn-card-nama { font-weight: 700; margin-bottom: .35rem; color: #fff; }
        .dn-card-row { display: flex; align-items: center; gap: .45rem; opacity: .92; padding: .1rem 0; }
        .dn-card-row i { width: 1rem; text-align: center; opacity: .75; }
        .dn-card-hint { margin-top: .35rem; padding-top: .35rem; border-top: 1px solid rgba(255,255,255,.12); font-size: .7rem; opacity: .75; font-style: italic; }
    </style>
@endonce

{{-- ═════════════════════════════════════════════════════════════
     RENDER DENAH
     ═════════════════════════════════════════════════════════════ --}}
<div class="denah-layout" data-denah data-clickable="{{ $clickable ? 1 : 0 }}">
    @unless ($hideHeader)
        <div class="dl-header">
            <h2 class="dl-title">
                {{ $displayTitle }}
                @if ($displaySubtitle)<small>{{ $displaySubtitle }}</small>@endif
            </h2>
            @if ($displayInfo)
                <div class="dl-info">{{ $displayInfo }}</div>
            @endif
        </div>
    @endunless

    <div class="dl-building">
        @if (isset($data['hall']))
            {{-- ═══ MODE HALL (Lantai 5) ═══ Satu ruangan utuh untuk diklik/dipesan,
                 bukan per meja/kursi — meja hanya ilustrasi tata letak, tidak masing-masing
                 bisa dipilih sendiri. --}}
            @php
                $hall = $data['hall'];
                $hallTag = ($hallInfo && $hallInfo['clickableThis']) ? 'a' : 'div';
                $hallStatus = $hallInfo['status'] ?? 'kosong';
            @endphp
            <{{ $hallTag }}
                @if ($hallTag === 'a') href="{{ $hallInfo['href'] }}" @endif
                class="dl-hall dl-hall-click {{ $hallStatus }} @if ($hallTag === 'a') clickable @endif"
                @if ($hallInfo)
                    data-kode="{{ $hallInfo['kode'] }}" data-id="{{ $hallInfo['f']?->id_fasilitas }}"
                    data-label="Convention Hall" data-nama="{{ $hallInfo['f']?->nama_fasilitas }}"
                    data-status="{{ $hallInfo['status'] }}" data-status-label="{{ $hallInfo['statusLabel'] }}"
                    data-kapasitas="{{ $hallInfo['f']?->kapasitas }}" data-harga="{{ $hallInfo['harga'] }}"
                    data-hint="{{ $hallInfo['hint'] }}"
                @endif
            >
                <span class="dl-hall-status {{ $hallStatus }}">{{ strtoupper($hallStatus) }}</span>

                <div class="dl-hall-panggung">{{ $hall['panggung'] ?? 'PANGGUNG' }}</div>

                <div class="dl-hall-rows">
                    @php $mejaChunks = array_chunk($hall['meja'] ?? [], 3); @endphp
                    @foreach ($mejaChunks as $chunk)
                        <div class="dl-hall-row">
                            @foreach ($chunk as $meja)
                                <div class="dl-table">
                                    <span class="dl-table-nomor">{{ $meja['nomor'] }}</span>
                                    <span class="dl-table-kursi">{{ $meja['kursi'] }} kursi</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                @if (! empty($hall['footer']))
                    <div class="dl-hall-footer">
                        @foreach ($hall['footer'] as $item)
                            @if (($item['tipe'] ?? '') === 'meja-panjang')
                                <div class="dl-mejapanjang">{{ $item['label'] }}</div>
                            @else
                                <div class="dl-sound">{{ $item['label'] }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </{{ $hallTag }}>
        @else
            {{-- ═══ MODE GRID + ROWS (Lantai 1, 2, 3A, 3B) ═══ --}}

            {{-- Grid utama --}}
            @if (isset($data['grid_main']))
                @php $grid = $data['grid_main']; @endphp
                <div class="dl-grid" style="grid-template-columns: repeat({{ $grid['cols'] }}, 1fr);">
                    @foreach ($grid['cells'] as $cell)
                        @php
                            $r = $renderRoom($cell);
                            $col = $cell['col'] ?? 1;
                            $row = $cell['row'] ?? 1;
                            $rowSpan = $cell['row_span'] ?? null;
                            $gridRow = $rowSpan ? "{$row} / span {$rowSpan}" : $row;
                        @endphp
                        <div style="grid-column: {{ $col }}; grid-row: {{ $gridRow }};">
                            @include('components.denah-room-tile', ['r' => $r])
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Baris SETELAH grid (khusus L3A: K1-K6 + R2 + R1) --}}
            @if (! empty($data['rows_after']))
                <div class="dl-row">
                    @foreach ($data['rows_after'] as $item)
                        @php $r = $renderRoom($item); @endphp
                        @include('components.denah-room-tile', ['r' => $r])
                    @endforeach
                </div>
            @endif

            {{-- Baris tambahan (koridor + area servis) --}}
            @foreach ($data['rows'] ?? [] as $row)
                <div class="dl-row">
                    @foreach ($row as $item)
                        @php $r = $renderRoom($item); @endphp
                        @include('components.denah-room-tile', ['r' => $r])
                    @endforeach
                </div>
            @endforeach
        @endif

        {{-- Kompas --}}
        <div class="dl-compass">N ↑</div>
    </div>
    <p class="dl-scroll-hint"><i class="bi bi-arrow-left-right"></i> Geser untuk melihat semua ruangan</p>

    {{-- Legenda --}}
    <div class="dl-legend">
        <div class="dl-legend-item"><span class="dl-swatch sw-ruangan"></span> Ruangan Kosong</div>
        <div class="dl-legend-item"><span class="dl-swatch sw-kuning"></span> Sebagian Terisi</div>
        <div class="dl-legend-item"><span class="dl-swatch sw-terisi"></span> Ruangan Penuh</div>
        <div class="dl-legend-item"><span class="dl-swatch sw-fasilitas"></span> Fasilitas Umum</div>
        <div class="dl-legend-item"><span class="dl-swatch sw-servis"></span> Area Servis</div>
    </div>

    {{-- Bar ringkasan pilihan — hanya pada mode pemesan (clickable), untuk memilih beberapa
         ruangan sekaligus sebelum lanjut isi jadwal, sama seperti alur sebelumnya. --}}
    @if ($clickable)
        <div class="dl-select-bar">
            <span class="fw-semibold small" data-count>0 fasilitas dipilih</span>
            <span class="dl-select-chips" data-chips></span>
            <a href="#" class="btn btn-brand btn-sm ms-auto disabled" data-go aria-disabled="true">
                Lihat Detail <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    @endif
</div>

{{-- Kartu detail mengambang — muncul saat kursor di dalam kotak ruangan. --}}
<div class="dn-card" data-dn-card hidden>
    <div class="dn-card-head">
        <span class="dn-card-kode" data-dn-kode></span>
        <span class="dn-card-status" data-dn-status></span>
    </div>
    <div class="dn-card-nama" data-dn-nama></div>
    <div class="dn-card-row" data-dn-luas-row><i class="bi bi-aspect-ratio"></i><span data-dn-luas></span></div>
    <div class="dn-card-row" data-dn-kap-row><i class="bi bi-people"></i><span data-dn-kap></span></div>
    <div class="dn-card-row" data-dn-harga-row hidden><i class="bi bi-tag"></i><span data-dn-harga></span></div>
    <div class="dn-card-hint" data-dn-hint hidden></div>
</div>

@once
<script>
    // window.initDenah — bisa dipanggil ulang setelah konten denah diganti via AJAX
    // (filter interaktif), bukan cuma sekali saat DOMContentLoaded.
    window.initDenah = function (root) {
        const fmtRupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

        (root || document).querySelectorAll('[data-denah]:not([data-denah-siap])').forEach(wrap => {
            wrap.setAttribute('data-denah-siap', '1');
            const clickable = wrap.dataset.clickable === '1';

            const barCount = wrap.querySelector('[data-count]');
            const barChips = wrap.querySelector('[data-chips]');
            const barGo = wrap.querySelector('[data-go]');
            const dipilih = new Map(); // id -> {label, kode, href}

            const render = () => {
                if (!barCount) return;
                barCount.textContent = dipilih.size + ' fasilitas dipilih' + (dipilih.size ? ':' : '');
                barChips.innerHTML = '';
                dipilih.forEach((v, id) => {
                    const a = document.createElement('a');
                    a.href = v.href;
                    a.textContent = v.label;
                    a.title = 'Isi jadwal ' + v.kode;
                    barChips.appendChild(a);
                });
                if (dipilih.size > 0) {
                    // Multi-select: ruangan pertama dibuka, sisanya diantrikan (?antrian=id2,id3)
                    // sehingga setelah tiap "Tambah ke Keranjang" otomatis lanjut ke ruangan berikutnya.
                    const entries = [...dipilih.entries()];
                    const antrian = entries.slice(1).map(([id]) => id);
                    barGo.classList.remove('disabled');
                    barGo.removeAttribute('aria-disabled');
                    barGo.href = entries[0][1].href + (antrian.length ? '&antrian=' + antrian.join(',') : '');
                    barGo.innerHTML = 'Lihat Detail' + (dipilih.size > 1 ? ' (' + dipilih.size + ' ruangan)' : '') + ' <i class="bi bi-arrow-right"></i>';
                } else {
                    barGo.classList.add('disabled');
                    barGo.setAttribute('aria-disabled', 'true');
                    barGo.href = '#';
                    barGo.innerHTML = 'Lihat Detail <i class="bi bi-arrow-right"></i>';
                }
            };

            wrap.querySelectorAll('.d-room.clickable, .dl-hall-click.clickable').forEach(room => {
                if (! clickable) return; // mode admin: link navigasi langsung, tanpa seleksi ganda.
                room.addEventListener('click', e => {
                    e.preventDefault();
                    const id = room.dataset.id;
                    if (! id) return;
                    if (dipilih.has(id)) {
                        dipilih.delete(id);
                        room.classList.remove('terpilih');
                    } else {
                        dipilih.set(id, { label: room.dataset.label, kode: room.dataset.kode, href: room.getAttribute('href') });
                        room.classList.add('terpilih');
                    }
                    render();
                });
            });

            // ===== Kartu detail mengambang, muncul saat kursor di dalam kotak ruangan =====
            const card = document.querySelector('[data-dn-card]');
            const positionCard = evt => {
                if (!card) return;
                const pad = 14;
                let x = evt.clientX + pad;
                let y = evt.clientY + pad;
                const rect = card.getBoundingClientRect();
                if (x + rect.width > window.innerWidth - 8) x = evt.clientX - rect.width - pad;
                if (y + rect.height > window.innerHeight - 8) y = evt.clientY - rect.height - pad;
                card.style.left = x + 'px';
                card.style.top = y + 'px';
            };
            const showCard = (room, evt) => {
                if (!card || !room.dataset.kode) return;
                const st = room.dataset.status || 'kosong';
                card.querySelector('[data-dn-kode]').textContent = room.dataset.label || room.dataset.kode;
                const badge = card.querySelector('[data-dn-status]');
                badge.textContent = room.dataset.statusLabel || '';
                badge.className = 'dn-card-status ' + st;
                card.querySelector('[data-dn-nama]').textContent = room.dataset.nama || room.dataset.kode;

                card.querySelector('[data-dn-luas]').textContent = room.dataset.luas
                    ? (room.dataset.luas.replace('.', ',') + ' m²') : '-';

                const kapRow = card.querySelector('[data-dn-kap-row]');
                if (room.dataset.kapasitas) {
                    kapRow.hidden = false;
                    card.querySelector('[data-dn-kap]').textContent = room.dataset.kapasitas + ' orang';
                } else {
                    kapRow.hidden = true;
                }

                const hargaRow = card.querySelector('[data-dn-harga-row]');
                if (room.dataset.harga) {
                    hargaRow.hidden = false;
                    card.querySelector('[data-dn-harga]').textContent = fmtRupiah(room.dataset.harga);
                } else {
                    hargaRow.hidden = true;
                }

                const hint = card.querySelector('[data-dn-hint]');
                if (room.dataset.hint) {
                    hint.hidden = false;
                    hint.textContent = room.dataset.hint;
                } else {
                    hint.hidden = true;
                }

                card.hidden = false;
                positionCard(evt);
            };
            const hideCard = () => { if (card) card.hidden = true; };

            wrap.querySelectorAll('.d-room.ruangan[data-kode], .dl-hall-click[data-kode]').forEach(room => {
                room.addEventListener('mouseenter', evt => showCard(room, evt));
                room.addEventListener('mousemove', evt => positionCard(evt));
                room.addEventListener('mouseleave', hideCard);
            });
        });
    };
    document.addEventListener('DOMContentLoaded', () => window.initDenah());
</script>
@endonce
