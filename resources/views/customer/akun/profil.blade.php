@extends('layouts.customer')
@section('title', 'Profil Saya')

@section('content')
    <div class="page-head mb-4" data-reveal>
        <p class="eyebrow-sm mb-2">Area Pemesan</p>
        <h1 class="h3 mb-1">Profil Saya</h1>
        <p class="text-muted mb-0 lead">Data ini dipakai sebagai identitas Anda saat melakukan reservasi ruangan BITC Cimahi.</p>
    </div>

    {{-- Hero card --}}
    @include('customer.akun.partials.hero', [
        'user' => $pemesan,
        'role' => 'Pemesan',
        'idPrefix' => 'BITC-P-',
        'idField' => 'id_pemesan',
        'active' => 'profil',
        'routes' => [
            'profil'       => 'customer.akun.profil',
            'password'     => 'customer.akun.password',
            'modal_edit'   => 'modalEditProfil',
            'modal_password' => 'modalUbahPassword',
        ],
    ])

    {{-- Info cards --}}
    <div class="pf-info-grid" data-reveal>
        {{-- Email full width --}}
        <div class="pf-info-card">
            <span class="pf-info-ic"><i class="bi bi-envelope-fill"></i></span>
            <div class="pf-info-body">
                <span class="pf-info-lbl">Email</span>
                <div class="pf-info-val">{{ $pemesan->email }}</div>
            </div>
        </div>

        {{-- 3 kolom: Telepon, Usia, Pekerjaan --}}
        <div class="pf-info-row">
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
        </div>

        {{-- Alamat full width --}}
        <div class="pf-info-card">
            <span class="pf-info-ic"><i class="bi bi-geo-alt-fill"></i></span>
            <div class="pf-info-body">
                <span class="pf-info-lbl">Alamat</span>
                <div class="pf-info-val">{{ $pemesan->alamat }}</div>
            </div>
        </div>
    </div>

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

                <form method="POST" action="{{ route('customer.akun.profil.update') }}"
                      data-confirm="Data profil Anda akan diperbarui." data-confirm-title="Simpan perubahan profil?"
                      data-icon="question" data-confirm-text="Ya, simpan">
                    @csrf
                    @method('PUT')
                    <div class="pf-modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="fl-field @error('nama_lengkap') is-invalid @enderror">
                                    <input name="nama_lengkap" id="flNamaLengkap" class="fl-input" placeholder=" "
                                           value="{{ old('nama_lengkap', $pemesan->nama_lengkap) }}" required>
                                    <label for="flNamaLengkap"><span class="fl-label-txt">Nama Lengkap</span></label>
                                </div>
                                @error('nama_lengkap') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="fl-field @error('usia') is-invalid @enderror">
                                    <input type="number" name="usia" id="flUsia" class="fl-input" placeholder=" " min="17" max="120"
                                           value="{{ old('usia', $pemesan->usia) }}" required>
                                    <label for="flUsia"><span class="fl-label-txt">Usia</span></label>
                                </div>
                                @error('usia') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-7">
                                <div class="fl-field @error('email') is-invalid @enderror">
                                    <input type="email" name="email" id="flEmailPemesan" class="fl-input" placeholder=" "
                                           value="{{ old('email', $pemesan->email) }}" required>
                                    <label for="flEmailPemesan"><span class="fl-label-txt">Email</span></label>
                                </div>
                                @error('email') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <div class="fl-field @error('no_telepon') is-invalid @enderror">
                                    <input type="tel" name="no_telepon" id="flNoTelepon" class="fl-input" inputmode="numeric"
                                           pattern="\+?[0-9]{8,20}" placeholder=" " value="{{ old('no_telepon', $pemesan->no_telepon) }}" required>
                                    <label for="flNoTelepon"><span class="fl-label-txt">No. Telepon</span></label>
                                </div>
                                @error('no_telepon') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('pekerjaan') is-invalid @enderror">
                                    <input name="pekerjaan" id="flPekerjaan" class="fl-input" placeholder=" "
                                           value="{{ old('pekerjaan', $pemesan->pekerjaan) }}" required>
                                    <label for="flPekerjaan"><span class="fl-label-txt">Pekerjaan / Instansi</span></label>
                                </div>
                                @error('pekerjaan') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('alamat') is-invalid @enderror">
                                    <textarea name="alamat" id="flAlamatPemesan" class="fl-input" rows="3" placeholder=" " required>{{ old('alamat', $pemesan->alamat) }}</textarea>
                                    <label for="flAlamatPemesan"><span class="fl-label-txt">Alamat Domisili</span></label>
                                </div>
                                @error('alamat') <div class="fl-err">{{ $message }}</div> @enderror
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

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById('modalEditProfil')).show();
            });
        </script>
    @endif

    <style>
        /* ══════ MODAL SHARED ══════ */
        .pf-modal { border:1px solid var(--line); border-radius:1.25rem; overflow:hidden;
            box-shadow:0 25px 50px -12px rgba(15,23,42,.25); }
        .pf-modal-head { display:flex; align-items:flex-start; justify-content:space-between;
            gap:1rem; padding:1.25rem 1.5rem; border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, var(--surface), #fff); }
        .pf-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
        .pf-modal-icon { display:grid; place-items:center; width:2.5rem; height:2.5rem;
            border-radius:.75rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-size:1.1rem; flex:none;
            box-shadow:0 6px 14px -3px rgba(15,118,110,.45); }
        .pf-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pf-modal-tag { font-size:.58rem; font-weight:800; letter-spacing:.06em;
            background:var(--primary-soft); color:var(--primary-dark);
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid var(--primary-softer); }
        .pf-modal-sub { font-size:.75rem; color:var(--muted); margin:.2rem 0 0; }
        .pf-modal-body { padding:1.35rem 1.5rem; }
        .fl-input::-ms-reveal, .fl-input::-ms-clear { display:none; }
        .pf-modal-foot { display:flex; justify-content:flex-end; gap:.55rem;
            padding:1rem 1.5rem; border-top:1px solid var(--line-soft); background:var(--surface); }
    </style>
@endsection