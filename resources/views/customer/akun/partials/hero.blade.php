@php
    $user = $user ?? auth()->user();
    $role = $role ?? 'Pemesan';
    $idPrefix = $idPrefix ?? 'BITC-P-';
    $idField = $idField ?? 'id_pemesan';
    $routes = $routes ?? [];

    // Belum ada fitur unggah foto profil — avatar selalu memakai inisial nama.
    $inisial = strtoupper(substr($user->nama_lengkap ?? '?', 0, 1));

    $modalEdit = $routes['modal_edit'] ?? 'modalEditProfil';
    $modalPassword = $routes['modal_password'] ?? 'modalUbahPassword';

    $idFormatted = $idPrefix . str_pad($user->{$idField} ?? 0, 5, '0', STR_PAD_LEFT);
@endphp

<style>
    /* ══════════════ HERO PROFIL ══════════════ */
    .pf-hero { overflow:hidden; border:1px solid var(--line); border-radius:1.25rem; }

    /* Banner ribbed hijau */
    .pf-hero-banner { position:relative; height:8.5rem; overflow:hidden;
        background:linear-gradient(135deg, #0c3648 0%, #0f526b 55%, #176b87 100%); }
    .pf-hero-banner::before { content:''; position:absolute; inset:0;
        background-image:repeating-linear-gradient(
            90deg,
            rgba(0,0,0,.15) 0px,
            rgba(0,0,0,.15) 2px,
            transparent 2px,
            transparent 10px
        ); }
    .pf-hero-banner::after { content:''; position:absolute; inset:0;
        background:radial-gradient(24rem 12rem at 15% -30%, rgba(255,255,255,.08), transparent 55%); }

    /* Tombol pojok kanan atas banner */
    .pf-hero-actions { position:absolute; top:1.15rem; right:1.25rem; left:1.25rem; z-index:2;
        display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.5rem; }
    @media (max-width: 575.98px) {
        .pf-hero-actions { top:.85rem; right:.85rem; left:.85rem; justify-content:center; }
        .pf-pill { padding:.5rem .8rem; font-size:.74rem; }
        .pf-hero-body { padding:0 1.1rem 1.25rem; }
        .pf-name { font-size:1.35rem; }
    }
    .pf-pill { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1rem;
        font-size:.78rem; font-weight:700; border-radius:.65rem; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease; cursor:pointer; border:0; }
    .pf-pill:hover { transform:translateY(-1px); }
    .pf-pill-dark { background:rgba(15,23,42,.35); color:#fff;
        border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(6px);
        box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .pf-pill-dark:hover { background:rgba(15,23,42,.5); color:#fff;
        box-shadow:0 6px 16px rgba(0,0,0,.22); }
    .pf-pill-light { background:#fff; color:var(--ink);
        box-shadow:0 4px 12px rgba(15,23,42,.15); }
    .pf-pill-light:hover { background:var(--primary-soft); color:var(--primary-dark);
        box-shadow:0 6px 16px rgba(15,23,42,.2); }
    .pf-pill i { font-size:.85rem; }

    /* Body hero — avatar + info */
    .pf-hero-body { position:relative; padding:0 1.75rem 1.5rem;
        background:#fff; }
    .pf-hero-row { display:grid; grid-template-columns:auto 1fr; gap:1.5rem;
        align-items:end; margin-top:-3.5rem; }
    @media (max-width: 575.98px) {
        .pf-hero-row { grid-template-columns:1fr; margin-top:-3rem; text-align:center; }
    }

    /* Avatar besar dengan dot online */
    .pf-avatar-box { position:relative; flex:none; align-self:flex-end; }
    @media (max-width: 575.98px) { .pf-avatar-box { align-self:center; } }
    .pf-avatar { width:7rem; height:7rem; border-radius:50%; overflow:hidden;
        display:grid; place-items:center;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-weight:800; font-size:2.5rem;
        border:5px solid #fff; box-shadow:0 12px 28px -8px rgba(15,23,42,.35); }
    .pf-avatar img { width:100%; height:100%; object-fit:cover; }
    .pf-avatar-dot { position:absolute; bottom:.4rem; right:.4rem;
        width:1.25rem; height:1.25rem; background:#10b981; border-radius:50%;
        border:3px solid #fff; box-shadow:0 2px 4px rgba(0,0,0,.15); }

    /* Info section */
    .pf-info { padding-bottom:.35rem; min-width:0; }
    @media (max-width: 575.98px) { .pf-info { padding-top:.75rem; } }

    .pf-id-chip { display:inline-flex; align-items:center; gap:.35rem;
        background:var(--primary-soft); color:var(--primary-dark);
        font-size:.72rem; font-weight:700; padding:.28rem .7rem;
        border-radius:9999px; border:1px solid var(--primary-softer);
        letter-spacing:.02em; margin-bottom:.45rem; }
    .pf-id-chip i { font-size:.85em; color:var(--primary); }

    .pf-name { font-size:1.65rem; font-weight:800; color:var(--ink);
        margin:0 0 .6rem; letter-spacing:-.02em; line-height:1.15; }

    .pf-chips { display:flex; flex-wrap:wrap; gap:.4rem; align-items:center; }
    @media (max-width: 575.98px) { .pf-chips { justify-content:center; } }
    .pf-chip { display:inline-flex; align-items:center; gap:.35rem;
        font-size:.72rem; font-weight:600; padding:.35rem .75rem;
        border-radius:.55rem; background:var(--surface); color:var(--muted);
        border:1px solid var(--line); }
    .pf-chip i { font-size:.85em; color:var(--soft); }
    .pf-chip.verified { background:var(--emerald-soft); color:#047857;
        border-color:#a7f3d0; font-weight:700; }
    .pf-chip.verified i { color:#059669; }

    /* ══════════════ INFO CARDS ══════════════ */
    .pf-info-grid { display:grid; gap:.85rem; margin-top:1.25rem; }

    .pf-info-card { display:flex; align-items:center; gap:1rem; min-width:0;
        padding:1.1rem 1.25rem; background:#fff;
        border:1px solid var(--line); border-radius:.95rem;
        transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .pf-info-card:hover { border-color:var(--primary-softer);
        box-shadow:0 8px 20px -10px rgba(15,60,73,.12); transform:translateY(-1px); }
    .pf-info-ic { display:grid; place-items:center; width:2.65rem; height:2.65rem;
        border-radius:.7rem; background:var(--primary-soft); color:var(--primary-dark);
        border:1px solid var(--primary-softer); font-size:1rem; flex:none; }
    .pf-info-body { min-width:0; flex:1; }
    .pf-info-lbl { display:block; font-size:.6rem; font-weight:800;
        letter-spacing:.1em; text-transform:uppercase; color:var(--soft);
        margin-bottom:.15rem; }
    .pf-info-val { font-size:.92rem; font-weight:700; color:var(--ink);
        word-break:break-word; line-height:1.4; }

    /* Grid row untuk 3 kolom */
    .pf-info-row { display:grid; grid-template-columns:repeat(3, 1fr); gap:.85rem; }
    @media (max-width: 767.98px) { .pf-info-row { grid-template-columns:1fr; } }
</style>

<div class="xcard pf-hero mb-3" data-reveal>
    {{-- Banner --}}
    <div class="pf-hero-banner">
        <div class="pf-hero-actions">
            {{-- Kedua tombol langsung membuka modalnya di halaman ini (satu klik). --}}
            <button type="button" class="pf-pill pf-pill-dark"
                    data-bs-toggle="modal" data-bs-target="#{{ $modalEdit }}">
                <i class="bi bi-pencil-fill"></i>Edit Profil
            </button>
            <button type="button" class="pf-pill pf-pill-light"
                    data-bs-toggle="modal" data-bs-target="#{{ $modalPassword }}">
                <i class="bi bi-shield-lock-fill"></i>Ubah Kata Sandi
            </button>
        </div>
    </div>

    {{-- Body: avatar + info --}}
    <div class="pf-hero-body">
        <div class="pf-hero-row">
            <div class="pf-avatar-box">
                <div class="pf-avatar">{{ $inisial }}</div>
                <span class="pf-avatar-dot" title="Aktif"></span>
            </div>

            <div class="pf-info">
                <span class="pf-id-chip">
                    <i class="bi bi-shield-check"></i>{{ $idFormatted }}
                </span>
                <h1 class="pf-name">{{ $user->nama_lengkap }}</h1>
                <div class="pf-chips">
                    <span class="pf-chip"><i class="bi bi-person-fill"></i>{{ $role }}</span>
                    @if ($user->created_at)
                        <span class="pf-chip">
                            <i class="bi bi-calendar3"></i>
                            Terdaftar sejak {{ $user->created_at->translatedFormat('d F Y') }}
                        </span>
                    @endif
                    <span class="pf-chip verified">
                        <i class="bi bi-patch-check-fill"></i>Terverifikasi
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

