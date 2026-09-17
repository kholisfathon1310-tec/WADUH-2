{{--
    Hero profil Pemesan — banner teal + avatar + info grid.
    Tombol Edit Profil & Ubah Password trigger modal Bootstrap.
    Fitur foto profil OPSIONAL — kalau route foto belum ada, tombol
    kamera + modal upload otomatis disembunyikan biar gak error.
    Prop: $active = 'profil' | 'password'
--}}
@php
    $pemesan = $pemesan ?? auth('customer')->user();
    $inisial = strtoupper(substr($pemesan->nama_lengkap ?? '?', 0, 1));

    // Aman kalau kolom `foto` belum ada — cek atribut dulu
    $fotoRaw = null;
    try { $fotoRaw = $pemesan->foto ?? null; } catch (\Throwable $e) { $fotoRaw = null; }
    $fotoUrl = $fotoRaw ? asset('storage/'.$fotoRaw) : null;

    // Fitur foto profil aktif kalau route-nya sudah didaftarkan
    $fotoAktif = Route::has('customer.akun.foto.update');
@endphp
<style>
    /* ─── HERO CARD ─── */
    .pf-hero { overflow:hidden; border:1px solid var(--line); }
    .pf-hero-banner { height:9rem; position:relative; overflow:hidden;
        background:linear-gradient(135deg,#0c3648 0%,#0f526b 45%,#176b87 100%); }
    .pf-hero-banner::before { content:''; position:absolute; inset:0;
        background-image:radial-gradient(rgba(255,255,255,.16) 1.5px, transparent 1.5px);
        background-size:22px 22px; opacity:.75; }
    .pf-hero-banner::after { content:''; position:absolute; inset:0;
        background:
            radial-gradient(28rem 14rem at 92% -30%, rgba(36,170,154,.35), transparent 55%),
            radial-gradient(20rem 10rem at 10% 120%, rgba(255,255,255,.15), transparent 60%);
        pointer-events:none; }

    .pf-hero-actions { position:absolute; top:1.1rem; right:1.25rem; display:flex; gap:.55rem; z-index:2; }
    .pf-pill { display:inline-flex; align-items:center; gap:.4rem; border:0; border-radius:.85rem;
        padding:.55rem 1rem; font-size:.78rem; font-weight:700; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease; cursor:pointer; }
    .pf-pill:hover { transform:translateY(-1px); }
    .pf-pill-light { background:#fff; color:var(--ink); box-shadow:0 4px 12px rgba(15,23,42,.18); }
    .pf-pill-light:hover { background:var(--primary-soft); color:var(--primary-dark); }
    .pf-pill-dark { background:rgba(15,23,42,.35); color:#fff; backdrop-filter:blur(10px);
        border:1px solid rgba(255,255,255,.25); box-shadow:0 4px 12px rgba(0,0,0,.2); }
    .pf-pill-dark:hover { background:rgba(15,23,42,.55); color:#fff; }
    .pf-pill i { font-size:.85rem; }

    .pf-hero-body { padding:0 1.75rem 1.75rem; position:relative; }
    .pf-identity { display:flex; align-items:flex-end; gap:1.5rem; flex-wrap:wrap;
        margin-top:-3.75rem; margin-bottom:1.5rem; }
    @media (max-width: 767.98px) {
        .pf-identity { margin-top:-3rem; }
    }

    .pf-avatar-box { position:relative; flex:none; align-self:flex-end; }
    .pf-avatar { width:7rem; height:7rem; border-radius:50%; display:grid; place-items:center; overflow:hidden;
        background:linear-gradient(135deg,var(--primary),var(--accent-light));
        color:#fff; font-weight:800; font-size:2.4rem;
        box-shadow:0 14px 28px -6px rgba(15,23,42,.35); border:4px solid #fff; }
    .pf-avatar img { width:100%; height:100%; object-fit:cover; }
    .pf-avatar-dot { position:absolute; bottom:.5rem; right:.5rem; width:1.15rem; height:1.15rem;
        background:var(--emerald); border-radius:50%; border:3px solid #fff;
        box-shadow:0 2px 4px rgba(0,0,0,.2); }
    .pf-avatar-edit { position:absolute; bottom:0; right:0; width:2.15rem; height:2.15rem;
        border-radius:50%; background:#fff; border:3px solid #fff;
        display:grid; place-items:center; cursor:pointer; color:var(--primary-dark);
        box-shadow:0 6px 14px -3px rgba(15,23,42,.3); transition:transform .15s ease, background .15s ease; }
    .pf-avatar-edit:hover { background:var(--primary-soft); transform:scale(1.08); }
    .pf-avatar-edit i { font-size:.85rem; }

    .pf-name-block { min-width:0; flex:1 1 16rem; padding-bottom:.35rem; }
    .pf-member-id { display:inline-flex; align-items:center; gap:.3rem; font-size:.65rem;
        font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--primary-dark);
        background:#fff; padding:.25rem .6rem; border-radius:9999px; margin-bottom:.5rem;
        box-shadow:0 2px 8px -2px rgba(15,23,42,.25); }
    .pf-member-id i { font-size:.9em; }
    .pf-name { font-weight:800; font-size:1.7rem; color:var(--ink); margin:0 0 .6rem;
        letter-spacing:-.02em; line-height:1.2; word-break:break-word;
        background:linear-gradient(135deg, var(--ink), var(--primary-dark));
        -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
    .pf-meta-row { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
    .pf-role-badge { display:inline-flex; align-items:center; gap:.35rem;
        background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
        color:var(--primary-dark); font-size:.72rem; font-weight:800; padding:.32rem .75rem;
        border-radius:.5rem; border:1px solid var(--primary-softer); }
    .pf-since { display:inline-flex; align-items:center; gap:.35rem; background:var(--surface); color:var(--muted);
        font-size:.72rem; font-weight:600; padding:.32rem .75rem; border-radius:.5rem; border:1px solid var(--line); }
    .pf-verified-badge { display:inline-flex; align-items:center; gap:.4rem; background:var(--emerald-soft);
        color:#047857; font-size:.75rem; font-weight:800; padding:.5rem .85rem;
        border-radius:.65rem; border:1px solid #a7f3d0;
        box-shadow:0 4px 10px -3px rgba(5,150,105,.15); }

    .pf-divider { border:0; border-top:1px dashed var(--line); margin:1.5rem 0; }

    .pf-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(15rem, 1fr)); gap:.85rem; }
    .pf-info-card { display:flex; align-items:flex-start; gap:.85rem; padding:1rem 1.1rem;
        background:linear-gradient(135deg, #fff, var(--surface));
        border:1px solid var(--line); border-radius:1rem;
        transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
    .pf-info-card:hover { border-color:var(--primary-softer); box-shadow:0 8px 20px -8px rgba(15,60,73,.12); transform:translateY(-2px); }
    .pf-info-ic { display:grid; place-items:center; width:2.6rem; height:2.6rem; border-radius:.75rem;
        background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
        border:1px solid var(--primary-softer); color:var(--primary-dark); font-size:1rem; flex:none; }
    .pf-info-body { min-width:0; flex:1; }
    .pf-info-lbl { display:block; font-size:.62rem; font-weight:800; letter-spacing:.1em;
        text-transform:uppercase; color:var(--soft); margin-bottom:.2rem; }
    .pf-info-val { font-size:.88rem; font-weight:700; color:var(--ink); overflow-wrap:anywhere;
        line-height:1.4; }
    .pf-info-full { grid-column:1 / -1; }

    .pf-foto-preview { width:8rem; height:8rem; border-radius:50%; margin:0 auto; overflow:hidden;
        background:linear-gradient(135deg, var(--primary), var(--accent-light));
        display:grid; place-items:center; color:#fff; font-size:2.6rem; font-weight:800;
        border:4px solid var(--surface); box-shadow:0 8px 20px -6px rgba(15,23,42,.25); }
    .pf-foto-preview img { width:100%; height:100%; object-fit:cover; }
</style>

<div class="xcard pf-hero mb-3" data-reveal>
    <div class="pf-hero-banner">
        <div class="pf-hero-actions">
            @if ($active === 'profil')
                <button type="button" class="pf-pill pf-pill-dark" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
                    <i class="bi bi-pencil-fill"></i>Edit Profil
                </button>
                <a href="{{ route('customer.akun.password') }}" class="pf-pill pf-pill-light">
                    <i class="bi bi-shield-lock-fill"></i>Ubah Kata Sandi
                </a>
            @else
                <a href="{{ route('customer.akun.profil') }}" class="pf-pill pf-pill-dark">
                    <i class="bi bi-pencil-fill"></i>Edit Profil
                </a>
                <button type="button" class="pf-pill pf-pill-light" data-bs-toggle="modal" data-bs-target="#modalUbahPassword">
                    <i class="bi bi-shield-lock-fill"></i>Ubah Kata Sandi
                </button>
            @endif
        </div>
    </div>

    <div class="pf-hero-body">
        <div class="pf-identity">
            <div class="pf-avatar-box">
                <div class="pf-avatar">
                    @if ($fotoUrl)
                        <img src="{{ $fotoUrl }}" alt="Foto {{ $pemesan->nama_lengkap }}">
                    @else
                        {{ $inisial }}
                    @endif
                </div>
                <span class="pf-avatar-dot" title="Aktif"></span>
                {{-- Tombol kamera hanya muncul kalau route foto tersedia --}}
                @if ($active === 'profil' && $fotoAktif)
                    <button type="button" class="pf-avatar-edit" data-bs-toggle="modal" data-bs-target="#modalFoto" title="Ubah foto profil">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                @endif
            </div>

            <div class="pf-name-block">
                <span class="pf-member-id"><i class="bi bi-patch-check"></i>BITC-P-{{ str_pad($pemesan->id_pemesan, 5, '0', STR_PAD_LEFT) }}</span>
                <h2 class="pf-name">{{ $pemesan->nama_lengkap }}</h2>
                <div class="pf-meta-row">
                    <span class="pf-role-badge"><i class="bi bi-person-check-fill"></i>Pemesan</span>
                    @if ($pemesan->created_at)
                        <span class="pf-since"><i class="bi bi-calendar3"></i>Terdaftar sejak {{ $pemesan->created_at->translatedFormat('d F Y') }}</span>
                    @endif
                    <span class="pf-verified-badge"><i class="bi bi-patch-check-fill"></i>Terverifikasi</span>
                </div>
            </div>
        </div>

        <hr class="pf-divider">

        <div class="pf-info-grid">
            <div class="pf-info-card pf-info-full">
                <span class="pf-info-ic"><i class="bi bi-envelope-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Email</span>
                    <div class="pf-info-val" title="{{ $pemesan->email }}">{!! str_replace('@', '@<wbr>', e($pemesan->email)) !!}</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-telephone-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">No. Telepon</span>
                    <div class="pf-info-val">{{ $pemesan->no_telepon }}</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-hourglass-split"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Usia</span>
                    <div class="pf-info-val">{{ $pemesan->usia }} tahun</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-briefcase-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Pekerjaan</span>
                    <div class="pf-info-val">{{ $pemesan->pekerjaan }}</div>
                </div>
            </div>
            <div class="pf-info-card pf-info-full">
                <span class="pf-info-ic"><i class="bi bi-geo-alt-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Alamat</span>
                    <div class="pf-info-val">{{ $pemesan->alamat }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal upload foto — HANYA di-render kalau fitur foto aktif (route ada) --}}
@if ($active === 'profil' && $fotoAktif)
    <div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1.4rem; border:1px solid var(--line); overflow:hidden;">
                <div style="padding:1.35rem 1.75rem; border-bottom:1px solid var(--line-soft); background:linear-gradient(135deg, var(--surface), #fff); display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;">
                    <div style="display:flex; align-items:flex-start; gap:.85rem;">
                        <span style="display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem; background:linear-gradient(135deg, var(--primary-dark), var(--primary)); color:#fff; font-size:1.15rem; box-shadow:0 8px 18px -4px rgba(23,107,135,.45);">
                            <i class="bi bi-camera-fill"></i>
                        </span>
                        <div>
                            <h5 style="font-size:1.05rem; font-weight:800; margin:0; color:var(--ink);">Ubah Foto Profil</h5>
                            <p style="font-size:.78rem; color:var(--muted); margin:.2rem 0 0;">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('customer.akun.foto.update') }}" enctype="multipart/form-data" id="formFotoProfil">
                    @csrf
                    <div style="padding:1.75rem;">
                        <div class="pf-foto-preview mb-3" id="pfFotoPreview">
                            @if ($fotoUrl)
                                <img src="{{ $fotoUrl }}" alt="Foto profil">
                            @else
                                {{ $inisial }}
                            @endif
                        </div>
                        <p class="text-center text-muted small mb-3">Foto ini akan tampil di sidebar &amp; riwayat reservasi.</p>
                        <label class="form-label">Pilih Foto Baru</label>
                        <input type="file" name="foto" id="inputFoto" accept="image/jpeg,image/png,image/webp" required class="form-control">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:.65rem; padding:1.15rem 1.75rem; border-top:1px solid var(--line-soft); background:var(--surface);">
                        @if ($fotoUrl && Route::has('customer.akun.foto.hapus'))
                            <button type="button" class="btn btn-brand-outline me-auto" style="color:var(--rose); border-color:var(--rose);"
                                    onclick="document.getElementById('formHapusFoto').submit()">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        @endif
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-upload me-1"></i>Simpan Foto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($fotoUrl && Route::has('customer.akun.foto.hapus'))
        <form id="formHapusFoto" method="POST" action="{{ route('customer.akun.foto.hapus') }}"
              data-confirm="Foto profil akan dihapus dan diganti dengan inisial nama." data-confirm-title="Hapus foto profil?"
              data-icon="warning" data-confirm-text="Ya, hapus" data-confirm-color="#e11d48" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <script>
        (function () {
            const input = document.getElementById('inputFoto');
            const preview = document.getElementById('pfFotoPreview');
            if (!input || !preview) return;
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'warning', title: 'Foto terlalu besar', text: 'Ukuran maksimal 2 MB.', confirmButtonColor: '#176b87' });
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (ev) => { preview.innerHTML = '<img src="' + ev.target.result + '" alt="Preview">'; };
                reader.readAsDataURL(file);
            });
        })();
    </script>
@endif