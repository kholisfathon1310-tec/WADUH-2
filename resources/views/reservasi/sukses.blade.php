@extends('layouts.customer')
@section('title', 'Reservasi Berhasil')

@section('content')
    <style>
        @keyframes sukses-pop { 0% { transform:scale(.7); opacity:0; } 70% { transform:scale(1.08); } 100% { transform:scale(1); opacity:1; } }
        @keyframes sukses-ring { 0% { box-shadow:0 0 0 0 rgba(13,138,95,.35); } 100% { box-shadow:0 0 0 18px rgba(13,138,95,0); } }
        .sukses-ic { animation:sukses-pop .5s cubic-bezier(.2,.9,.3,1.3) both, sukses-ring 1.8s ease-out 1; }
        .sukses-kode { position:relative; overflow:hidden; }
        .sukses-kode::before { content:''; position:absolute; inset:0; background:radial-gradient(20rem 10rem at 50% -30%, rgba(14,107,125,.08), transparent 60%); pointer-events:none; }
        .sukses-next { text-align:left; display:flex; flex-direction:column; gap:.8rem; }
        .sukses-next .item { display:flex; gap:.8rem; align-items:flex-start; }
        .sukses-next .num { display:grid; place-items:center; width:1.9rem; height:1.9rem; border-radius:50%; background:var(--primary-soft); color:var(--primary); font-weight:800; font-size:.8rem; flex:none; }
        .sukses-next .tx b { font-family:'Plus Jakarta Sans',sans-serif; font-size:.86rem; color:var(--ink); display:block; }
        .sukses-next .tx span { font-size:.78rem; color:var(--muted); }
    </style>
    <div class="xcard mx-auto text-center p-5" style="max-width:640px;" data-reveal>
        <div class="sukses-ic mx-auto mb-3" style="width:5.2rem;height:5.2rem;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#e2f7ef,#d4f2e5);color:#0d8a5f;font-size:2.5rem;">
            <i class="bi bi-check-lg"></i>
        </div>
        <p class="eyebrow-sm mb-1 text-center" style="justify-content:center">Konfirmasi Reservasi</p>
        <h1 class="h4 mb-2">Reservasi Berhasil Diajukan</h1>
        <p class="text-muted">Status awal <span class="badge text-bg-warning">Menunggu</span> persetujuan admin. Simpan kode berikut untuk mengecek status kapan saja.</p>

        <div class="sukses-kode p-4 rounded-4 my-3" style="background:var(--surface); border:1.5px dashed #b8cad5;">
            <span class="text-muted small d-block mb-1">Kode Reservasi Anda</span>
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                <div class="display-6 fw-bold mb-0" style="color:var(--primary); letter-spacing:.08em;">{{ $checkout['kode_transaksi'] }}</div>
                <button type="button" class="btn btn-sm btn-brand-outline" data-salin="{{ $checkout['kode_transaksi'] }}">
                    <i class="bi bi-clipboard me-1"></i>Salin
                </button>
            </div>
            @if (count($checkout['kode_reservasi']) > 1)
                <span class="text-muted small">Satu kode untuk {{ count($checkout['kode_reservasi']) }} ruangan yang Anda pesan.</span>
            @endif
        </div>

        <div class="p-3 rounded-4 mb-4" style="background:var(--surface)">
            <div class="sukses-next">
                <div class="item">
                    <span class="num">1</span>
                    <div class="tx"><b>Menunggu verifikasi admin</b><span>Admin BITC akan memeriksa jadwal &amp; kelengkapan data Anda.</span></div>
                </div>
                <div class="item">
                    <span class="num">2</span>
                    <div class="tx"><b>Pantau status di Reservasi Saya</b><span>Status berubah menjadi Disetujui / Ditolak setelah diproses.</span></div>
                </div>
                <div class="item">
                    <span class="num">3</span>
                    <div class="tx"><b>Gunakan ruangan sesuai jadwal</b><span>Datang sesuai tanggal &amp; jam yang telah disetujui.</span></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route('customer.reservasi-saya.index') }}" class="btn btn-brand px-4"><i class="bi bi-journal-check me-1"></i>Reservasi Saya</a>
            <a href="{{ route('reservasi.index') }}" class="btn btn-brand-outline px-4">Reservasi Lagi</a>
        </div>
    </div>
@endsection
