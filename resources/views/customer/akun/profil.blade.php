@extends('layouts.customer')
@section('title', 'Profil')

@section('content')
    <div class="page-head mb-4" data-reveal>
        <p class="eyebrow-sm mb-2">Akun</p>
        <h1 class="h3 mb-1">Profil</h1>
        <p class="text-muted mb-0 lead">Data ini dipakai sebagai data diri Anda saat melakukan reservasi.</p>
    </div>

    @include('customer.akun.partials.hero', ['active' => 'profil', 'pemesan' => $pemesan])

    {{-- ══════════════ MODAL EDIT PROFIL ══════════════ --}}
    <div class="modal fade" id="modalEditProfil" tabindex="-1" aria-labelledby="modalEditProfilLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content pf-modal">
                <div class="pf-modal-head">
                    <div class="pf-modal-head-left">
                        <span class="pf-modal-icon"><i class="bi bi-person-lines-fill"></i></span>
                        <div>
                            <h5 class="pf-modal-title" id="modalEditProfilLabel">
                                Edit Profil <span class="pf-modal-tag">IDENTITAS</span>
                            </h5>
                            <p class="pf-modal-sub">Perbarui informasi identitas akun pemesan.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('customer.akun.profil.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="pf-modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="pf-modal-lbl">Nama Lengkap</label>
                                <div class="pf-input-group @error('nama_lengkap') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-person"></i></span>
                                    <input name="nama_lengkap" class="pf-input"
                                           value="{{ old('nama_lengkap', $pemesan->nama_lengkap) }}" required>
                                </div>
                                @error('nama_lengkap') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="pf-modal-lbl">
                                    Usia <span class="pf-hint">Tahun</span>
                                </label>
                                <div class="pf-input-group @error('usia') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-hourglass-split"></i></span>
                                    <input type="number" name="usia" class="pf-input" min="17" max="120"
                                           value="{{ old('usia', $pemesan->usia) }}" required>
                                </div>
                                @error('usia') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-7">
                                <label class="pf-modal-lbl">Email Resmi</label>
                                <div class="pf-input-group @error('email') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="pf-input"
                                           value="{{ old('email', $pemesan->email) }}" required>
                                </div>
                                @error('email') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="pf-modal-lbl">No. Telepon Aktif</label>
                                <div class="pf-input-group @error('no_telepon') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" name="no_telepon" class="pf-input" inputmode="tel"
                                           placeholder="0812xxxxxxx"
                                           value="{{ old('no_telepon', $pemesan->no_telepon) }}" required>
                                </div>
                                @error('no_telepon') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="pf-modal-lbl">Pekerjaan / Bidang Instansi</label>
                                <div class="pf-input-group @error('pekerjaan') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-briefcase"></i></span>
                                    <input name="pekerjaan" class="pf-input"
                                           value="{{ old('pekerjaan', $pemesan->pekerjaan) }}" required>
                                </div>
                                @error('pekerjaan') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="pf-modal-lbl">Alamat Lengkap Domisili</label>
                                <div class="pf-input-group pf-input-group-multi @error('alamat') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-geo-alt"></i></span>
                                    <textarea name="alamat" class="pf-input" rows="2" required>{{ old('alamat', $pemesan->alamat) }}</textarea>
                                </div>
                                @error('alamat') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="pf-modal-foot">
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Buka modal otomatis kalau ada error validasi — ditunggu sampai DOMContentLoaded
         karena script ini dirender sebelum <script src="bootstrap.bundle.min.js"> di layout. --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const m = new bootstrap.Modal(document.getElementById('modalEditProfil'));
                m.show();
            });
        </script>
    @endif

    <style>
        /* ══════ MODAL STYLE ══════ */
        .pf-modal { border:1px solid var(--line); border-radius:1.4rem; overflow:hidden;
            box-shadow:0 25px 50px -12px rgba(15,23,42,.25); }
        .pf-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;
            padding:1.35rem 1.75rem; border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, var(--surface), #fff); }
        .pf-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
        .pf-modal-icon { display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-size:1.15rem; flex:none;
            box-shadow:0 8px 18px -4px rgba(23,107,135,.45); }
        .pf-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pf-modal-tag { font-size:.6rem; font-weight:800; letter-spacing:.06em;
            background:var(--primary-soft); color:var(--primary-dark);
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid var(--primary-softer); }
        .pf-modal-sub { font-size:.78rem; color:var(--muted); margin:.2rem 0 0; }
        .pf-modal-head .btn-close { flex:none; margin-top:.2rem; }

        .pf-modal-body { padding:1.5rem 1.75rem; }
        .pf-modal-lbl { display:flex; align-items:center; justify-content:space-between; gap:.5rem;
            font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
            color:#334155; margin-bottom:.5rem; }
        .pf-hint { font-size:.65rem; font-weight:600; color:var(--soft); text-transform:none; letter-spacing:0; }

        .pf-input-group { display:flex; align-items:stretch; border:1px solid var(--line);
            border-radius:.85rem; background:#fff; overflow:hidden;
            transition:border-color .15s ease, box-shadow .15s ease; }
        .pf-input-group.pf-input-group-multi { align-items:stretch; }
        .pf-input-group:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .pf-input-group.is-invalid { border-color:var(--rose); }
        .pf-field-err { font-size:.75rem; font-weight:600; color:var(--rose); margin-top:.35rem; }
        .pf-input-ic { display:grid; place-items:center; width:2.6rem; color:var(--primary);
            border-right:1px solid var(--line-soft); background:var(--surface); flex:none; font-size:.9rem; }
        .pf-input { flex:1; border:0; padding:.7rem .95rem; font-size:.88rem; font-weight:600;
            color:var(--ink); background:transparent; outline:none; resize:vertical; min-width:0; }

        .pf-modal-foot { display:flex; align-items:center; justify-content:flex-end; gap:.65rem;
            padding:1.15rem 1.75rem; border-top:1px solid var(--line-soft); background:var(--surface); }

        @media (max-width: 575.98px) {
            .pf-modal-head, .pf-modal-body, .pf-modal-foot { padding-left:1.15rem; padding-right:1.15rem; }
        }
    </style>
@endsection