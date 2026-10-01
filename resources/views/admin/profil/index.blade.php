@extends('admin.layouts.app')
@section('title', 'Profil')

@section('content')
    <style>
        /* Judul halaman — layout admin belum punya .page-head/.eyebrow-sm (khusus Area Pemesan),
           jadi didefinisikan lokal di sini biar sama persis gayanya. */
        .eyebrow-sm { display:inline-block; color:var(--primary-dark); font-size:.65rem; font-weight:800;
            letter-spacing:.14em; text-transform:uppercase;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            padding:.3rem .7rem; border-radius:.5rem; border:1px solid var(--primary-softer); }
        .page-head h1 { font-weight:800; letter-spacing:-.02em; }
        .page-head .lead { color:var(--muted); font-size:.9rem; font-weight:500; margin-top:.25rem; }

        /* ══════════════ HERO PROFIL — pola sama dengan hero Profil Pemesan
           (customer/akun/partials/hero.blade.php), warna & isi disesuaikan untuk Admin. ══════════════ */
        .pf-hero { overflow:hidden; border:1px solid var(--line); border-radius:1.25rem; }
        .pf-hero-banner { position:relative; height:8.5rem; overflow:hidden;
            background:linear-gradient(135deg, #0c3648 0%, #0f526b 55%, #176b87 100%); }
        .pf-hero-banner::before { content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.16) 1.5px, transparent 1.5px);
            background-size:22px 22px; opacity:.75; }
        .pf-hero-banner::after { content:''; position:absolute; inset:0;
            background:radial-gradient(24rem 12rem at 15% -30%, rgba(255,255,255,.1), transparent 55%);
            pointer-events:none; }

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
        .pf-pill-dark:hover { background:rgba(15,23,42,.5); color:#fff; box-shadow:0 6px 16px rgba(0,0,0,.22); }
        .pf-pill-light { background:#fff; color:var(--ink); box-shadow:0 4px 12px rgba(15,23,42,.15); }
        .pf-pill-light:hover { background:var(--primary-soft); color:var(--primary-dark); box-shadow:0 6px 16px rgba(15,23,42,.2); }
        .pf-pill i { font-size:.85rem; }

        .pf-hero-body { position:relative; padding:0 1.75rem 1.5rem; background:#fff; }
        .pf-hero-row { display:grid; grid-template-columns:auto 1fr; gap:1.5rem; align-items:end; margin-top:-3.5rem; }
        @media (max-width: 575.98px) { .pf-hero-row { grid-template-columns:1fr; margin-top:-3rem; text-align:center; } }

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
            border-radius:.55rem; background:var(--surface); color:var(--muted); border:1px solid var(--line); }
        .pf-chip i { font-size:.85em; color:var(--soft); }
        .pf-chip.verified { background:var(--emerald-soft); color:#047857; border-color:#a7f3d0; font-weight:700; }
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
            letter-spacing:.1em; text-transform:uppercase; color:var(--soft); margin-bottom:.15rem; }
        .pf-info-val { font-size:.92rem; font-weight:700; color:var(--ink); word-break:break-word; line-height:1.4; }
        .pf-info-val.pf-info-empty { color:var(--muted); font-style:italic; font-weight:500; }
        .pf-info-row { display:grid; grid-template-columns:repeat(2, 1fr); gap:.85rem; }
        @media (max-width: 575.98px) { .pf-info-row { grid-template-columns:1fr; } }
    </style>

    {{-- Hero card --}}
    <div class="xcard pf-hero mb-3" data-reveal>
        <div class="pf-hero-banner">
            <div class="pf-hero-actions">
                <button type="button" class="pf-pill pf-pill-dark" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
                    <i class="bi bi-pencil-fill"></i>Edit Profil
                </button>
                <button type="button" class="pf-pill pf-pill-light" data-bs-toggle="modal" data-bs-target="#modalUbahPassword">
                    <i class="bi bi-shield-lock-fill"></i>Ubah Kata Sandi
                </button>
            </div>
        </div>

        <div class="pf-hero-body">
            <div class="pf-hero-row">
                <div class="pf-avatar-box">
                    {{-- Belum ada fitur unggah foto — avatar selalu memakai inisial nama. --}}
                    <div class="pf-avatar">{{ strtoupper(substr($me->nama_admin, 0, 1)) }}</div>
                    <span class="pf-avatar-dot" title="Aktif"></span>
                </div>

                <div class="pf-info">
                    <span class="pf-id-chip">
                        <i class="bi bi-shield-check"></i>BITC-A-{{ str_pad($me->id_admin, 5, '0', STR_PAD_LEFT) }}
                    </span>
                    <h1 class="pf-name">{{ $me->nama_admin }}</h1>
                    <div class="pf-chips">
                        <span class="pf-chip"><i class="bi bi-patch-check-fill"></i>Administrator</span>
                        @if ($me->created_at)
                            <span class="pf-chip">
                                <i class="bi bi-calendar3"></i>
                                Terdaftar sejak {{ $me->created_at->translatedFormat('d F Y') }}
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

    {{-- Info cards --}}
    <div class="pf-info-grid" data-reveal>
        <div class="pf-info-card">
            <span class="pf-info-ic"><i class="bi bi-envelope-fill"></i></span>
            <div class="pf-info-body">
                <span class="pf-info-lbl">Email</span>
                <div class="pf-info-val">{{ $me->email }}</div>
            </div>
        </div>

        <div class="pf-info-row">
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-whatsapp"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">No. WhatsApp</span>
                    <div class="pf-info-val @unless($me->no_whatsapp) pf-info-empty @endunless">{{ $me->no_whatsapp ?: 'Belum diisi' }}</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-shield-lock-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Kata Sandi</span>
                    <div class="pf-info-val">••••••••••</div>
                </div>
            </div>
        </div>

        <div class="pf-info-card">
            <span class="pf-info-ic"><i class="bi bi-geo-alt-fill"></i></span>
            <div class="pf-info-body">
                <span class="pf-info-lbl">Alamat</span>
                <div class="pf-info-val @unless($me->alamat) pf-info-empty @endunless">{{ $me->alamat ?: 'Belum diisi' }}</div>
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL EDIT PROFIL — satu kolom, berurutan dari atas ke bawah ══════════════ --}}
    <div class="modal fade" id="modalEditProfil" tabindex="-1" data-bs-focus="false"
         aria-labelledby="modalEditProfilLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content pf-modal">
                <div class="pf-modal-head">
                    <div class="pf-modal-head-left">
                        <span class="pf-modal-icon"><i class="bi bi-person-lines-fill"></i></span>
                        <div>
                            <h5 class="pf-modal-title" id="modalEditProfilLabel">
                                Edit Profil <span class="pf-modal-tag">IDENTITAS</span>
                            </h5>
                            <p class="pf-modal-sub">Perbarui data identitas akun administrator.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('admin.profil.update') }}"
                      data-confirm="Data profil Anda akan diperbarui." data-confirm-title="Simpan perubahan profil?"
                      data-icon="question" data-confirm-text="Ya, simpan">
                    @csrf
                    @method('PUT')
                    <div class="pf-modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="fl-field @error('nama_admin') is-invalid @enderror">
                                    <input type="text" name="nama_admin" id="flNamaAdmin" class="fl-input" placeholder=" " maxlength="255"
                                           autocomplete="name" value="{{ old('nama_admin', $me->nama_admin) }}" required>
                                    <label for="flNamaAdmin"><span class="fl-label-txt">Nama</span></label>
                                </div>
                                @error('nama_admin') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field otp-field @error('email') is-invalid @enderror">
                                    <input type="email" name="email" id="flEmailAdmin" class="fl-input" placeholder=" " maxlength="255"
                                           autocomplete="email" value="{{ old('email', $me->email) }}" required>
                                    <label for="flEmailAdmin"><span class="fl-label-txt">Email</span></label>
                                    @include('partials.otp-email-tombol', ['emailId' => 'flEmailAdmin'])
                                </div>
                                @error('email') <div class="fl-err otp-galat-email">{{ $message }}</div> @enderror
                                @include('partials.otp-email', [
                                    'emailId' => 'flEmailAdmin', 'url' => route('admin.profil.otp'), 'urlVerifikasi' => route('admin.profil.otp.verifikasi'),
                                    'emailAwal' => $me->email, 'status' => $otpStatus ?? null, 'terverifikasi' => $otpTerverifikasi ?? false,
                                ])
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('no_whatsapp') is-invalid @enderror">
                                    <input type="tel" name="no_whatsapp" id="flWhatsappAdmin" class="fl-input" inputmode="numeric" maxlength="20"
                                           pattern="\+?[0-9]{8,20}" placeholder=" " autocomplete="tel"
                                           value="{{ old('no_whatsapp', $me->no_whatsapp) }}" required>
                                    <label for="flWhatsappAdmin"><span class="fl-label-txt">No. WhatsApp</span></label>
                                </div>
                                @error('no_whatsapp') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('alamat') is-invalid @enderror">
                                    <textarea name="alamat" id="flAlamatAdmin" class="fl-input" rows="3" placeholder=" " maxlength="500" required>{{ old('alamat', $me->alamat) }}</textarea>
                                    <label for="flAlamatAdmin"><span class="fl-label-txt">Alamat</span></label>
                                </div>
                                @error('alamat') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="pf-modal-foot">
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL UBAH KATA SANDI (bersama Pemesan) ══════════════ --}}
    @include('customer.akun.partials.modal-ubah-sandi', [
        'aksi'      => route('admin.profil.password'),
        'kolomBaru' => 'password_baru',
    ])

    @php
        // Modal dibuka kembali otomatis HANYA untuk galat miliknya sendiri.
        $galatProfil = $errors->hasAny(['nama_admin', 'email', 'no_whatsapp', 'alamat']);
        $galatSandi = $errors->hasAny(['password_lama', 'password_baru', 'password_baru_confirmation']);
        $bukaModal = $galatSandi ? 'modalUbahPassword' : ($galatProfil ? 'modalEditProfil' : null);
    @endphp
    @if ($bukaModal)
        {{-- Ditunggu sampai DOMContentLoaded karena skrip ini dirender SEBELUM berkas
             bootstrap.bundle di layout, jadi objek `bootstrap` belum ada bila dipanggil langsung. --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById(@json($bukaModal))).show();
            });
        </script>
    @endif
@endsection
