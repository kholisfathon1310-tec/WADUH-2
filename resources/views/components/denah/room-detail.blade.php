{{--
    <x-denah.room-detail> — RoomDetail: panel detail ruangan (BUKAN modal), muncul
    di sebelah kanan denah saat sebuah ruangan diklik. Markup statis; seluruh isi
    diisi oleh JS (lihat components/denah.blade.php) lewat atribut data-* pada tiap
    ruangan SVG — tidak ada query/logic tambahan di sini.
--}}
<aside class="dn-detail" data-dn-detail>
    <div class="dn-detail-empty" data-dn-empty>
        <i class="bi bi-hand-index-thumb"></i>
        <p>Klik salah satu ruangan berwarna pada denah untuk melihat detailnya di sini.</p>
    </div>

    <div class="dn-detail-body" data-dn-body hidden>
        <button type="button" class="dn-detail-close" data-dn-close aria-label="Tutup detail"><i class="bi bi-x-lg"></i></button>

        <span class="dn-detail-status" data-dd-status></span>
        <h3 class="dn-detail-kode" data-dd-kode></h3>
        <p class="dn-detail-nama" data-dd-nama></p>

        <dl class="dn-detail-specs">
            <div><dt><i class="bi bi-aspect-ratio"></i>Luas</dt><dd data-dd-luas>-</dd></div>
            <div><dt><i class="bi bi-people"></i>Kapasitas</dt><dd data-dd-kapasitas>-</dd></div>
        </dl>

        <div class="dn-detail-harga" data-dd-harga-wrap hidden>
            <span>Harga sewa</span>
            <strong data-dd-harga></strong>
        </div>

        <p class="dn-detail-desk" data-dd-deskripsi hidden></p>

        <a href="#" class="dn-detail-btn" data-dd-btn>Lihat Detail <i class="bi bi-arrow-right"></i></a>
    </div>
</aside>

@once
    <style>
        .dn-detail {
            flex: none; width: 280px; background:#fff; border:1px solid #E2E8F0; border-radius:1.1rem;
            padding: 1.1rem; box-shadow: 0 10px 28px rgba(15,23,42,.06);
            display:flex; flex-direction:column;
        }
        .dn-detail-empty { margin:auto; text-align:center; color:#94a3b8; padding: 1.5rem .5rem; }
        .dn-detail-empty i { font-size: 1.6rem; display:block; margin-bottom:.6rem; color:#cbd5e1; }
        .dn-detail-empty p { font-size:.82rem; margin:0; }

        .dn-detail-body { position:relative; }
        .dn-detail-close { position:absolute; top:0; right:0; border:none; background:#F8FAFC; color:#64748b; width:1.9rem; height:1.9rem; border-radius:.6rem; display:grid; place-items:center; font-size:.75rem; }
        .dn-detail-close:hover { background:#eef2f6; color:#176B87; }

        .dn-detail-status { display:inline-flex; align-items:center; gap:.35rem; font-size:.72rem; font-weight:800; padding:.28rem .65rem; border-radius:2rem; margin-bottom:.6rem; }
        .dn-detail-status.hijau { background:#dcfce7; color:#15803d; }
        .dn-detail-status.kuning { background:#fef3c7; color:#92400e; }
        .dn-detail-status.merah { background:#fee2e2; color:#b91c1c; }

        .dn-detail-kode { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.35rem; margin:0 0 .1rem; color:#0f172a; }
        .dn-detail-nama { font-size:.82rem; color:#64748b; margin:0 0 .9rem; }

        .dn-detail-specs { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin:0 0 .9rem; }
        .dn-detail-specs dt { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; display:flex; align-items:center; gap:.3rem; margin-bottom:.15rem; }
        .dn-detail-specs dd { font-size:.92rem; font-weight:700; color:#1e293b; margin:0; }

        .dn-detail-harga { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:.85rem; padding:.65rem .8rem; margin-bottom:.9rem; display:flex; justify-content:space-between; align-items:center; gap:.5rem; }
        .dn-detail-harga span { font-size:.78rem; color:#64748b; font-weight:600; }
        .dn-detail-harga strong { font-size:1rem; color:#176B87; font-family:'Plus Jakarta Sans',sans-serif; }

        .dn-detail-desk { font-size:.82rem; color:#64748b; line-height:1.5; border-top:1px dashed #E2E8F0; padding-top:.75rem; margin-bottom:.9rem; }

        .dn-detail-btn { display:flex; align-items:center; justify-content:center; gap:.4rem; background:#176B87; color:#fff; font-weight:700; font-size:.88rem; padding:.68rem 1rem; border-radius:.7rem; text-decoration:none; margin-top:auto; transition:background .15s, transform .15s; }
        .dn-detail-btn:hover { background:#0c5f58; color:#fff; transform:translateY(-1px); }

        @media (max-width: 991.98px) {
            .dn-detail { width:100%; }
        }
    </style>
@endonce
