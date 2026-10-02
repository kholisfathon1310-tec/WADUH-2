@extends('layouts.customer')
@section('title', 'Profil Saya')

@section('content')
    {{-- Hero card --}}
    @include('customer.akun.partials.hero', [
        'user' => $pemesan,
        'role' => 'Pemesan',
        'routes' => [
            'modal_edit'     => 'modalEditProfil',
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
                            <p class="pf-modal-sub">Perbarui data identitas akun pemesan.</p>
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
                            <div class="col-12">
                                <div class="fl-field @error('nama_lengkap') is-invalid @enderror">
                                    <input type="text" name="nama_lengkap" id="flNamaLengkap" class="fl-input" placeholder=" " minlength="3" maxlength="100"
                                           pattern="[\p{L}\s.'\-]+" data-pesan-pola="Nama lengkap hanya boleh berisi huruf."
                                           autocomplete="name" value="{{ old('nama_lengkap', $pemesan->nama_lengkap) }}" required>
                                    <label for="flNamaLengkap"><span class="fl-label-txt">Nama Lengkap</span></label>
                                </div>
                                @error('nama_lengkap') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field otp-field @error('email') is-invalid @enderror">
                                    <input type="email" name="email" id="flEmailPemesan" class="fl-input" placeholder=" " maxlength="150"
                                           autocomplete="email" value="{{ old('email', $pemesan->email) }}" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" data-pesan-pola="Format email tidak valid, contoh: nama@email.com." required>
                                    <label for="flEmailPemesan"><span class="fl-label-txt">Email</span></label>
                                    @include('partials.otp-email-tombol', ['emailId' => 'flEmailPemesan'])
                                </div>
                                @error('email') <div class="fl-err otp-galat-email">{{ $message }}</div> @enderror
                                @include('partials.otp-email', [
                                    'emailId' => 'flEmailPemesan', 'url' => route('customer.akun.profil.otp'), 'urlVerifikasi' => route('customer.akun.profil.otp.verifikasi'),
                                    'emailAwal' => $pemesan->email, 'status' => $otpStatus ?? null, 'terverifikasi' => $otpTerverifikasi ?? false,
                                ])
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('no_telepon') is-invalid @enderror">
                                    <input type="tel" name="no_telepon" id="flNoTelepon" class="fl-input" inputmode="numeric" maxlength="16"
                                           pattern="\+?[0-9]{10,15}" data-pesan-pola="No. telepon harus berupa angka 10–15 digit." placeholder=" " autocomplete="tel"
                                           value="{{ old('no_telepon', $pemesan->no_telepon) }}" required>
                                    <label for="flNoTelepon"><span class="fl-label-txt">No. Telepon</span></label>
                                </div>
                                @error('no_telepon') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('usia') is-invalid @enderror">
                                    <input type="number" name="usia" id="flUsia" class="fl-input" placeholder=" " min="17" max="100" step="1"
                                           inputmode="numeric" value="{{ old('usia', $pemesan->usia) }}" required
                                           data-pesan-min="Usia minimal 17 tahun." data-pesan-maks="Usia maksimal 100 tahun.">
                                    <label for="flUsia"><span class="fl-label-txt">Usia</span></label>
                                </div>
                                @error('usia') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('pekerjaan') is-invalid @enderror">
                                    <input type="text" name="pekerjaan" id="flPekerjaan" class="fl-input" placeholder=" " minlength="3" maxlength="100"
                                           pattern="[\p{L}\s.,'\/&amp;\(\)\-]+" data-pesan-pola="Pekerjaan hanya boleh berisi huruf."
                                           value="{{ old('pekerjaan', $pemesan->pekerjaan) }}" required>
                                    <label for="flPekerjaan"><span class="fl-label-txt">Pekerjaan / Instansi</span></label>
                                </div>
                                @error('pekerjaan') <div class="fl-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="fl-field @error('alamat') is-invalid @enderror">
                                    <textarea name="alamat" id="flAlamatPemesan" class="fl-input" rows="3" placeholder=" " minlength="10" maxlength="500" required>{{ old('alamat', $pemesan->alamat) }}</textarea>
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

    {{-- ══════════════ MODAL UBAH KATA SANDI (bersama Admin) ══════════════ --}}
    @include('customer.akun.partials.modal-ubah-sandi', [
        'aksi'      => route('customer.akun.password.update'),
        'kolomBaru' => 'password',
    ])

    @php
        // Modal dibuka kembali otomatis HANYA untuk galat miliknya sendiri, atau bila halaman
        // dituju lewat tautan lama /profil/password (?ubah=sandi).
        $galatProfil = $errors->hasAny(['nama_lengkap', 'email', 'no_telepon', 'usia', 'pekerjaan', 'alamat']);
        $galatSandi = $errors->hasAny(['password_lama', 'password', 'password_confirmation']);
        $bukaModal = ($galatSandi || request('ubah') === 'sandi') ? 'modalUbahPassword' : ($galatProfil ? 'modalEditProfil' : null);
    @endphp
    @if ($bukaModal)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById(@json($bukaModal))).show();
                // Buang ?ubah=sandi dari alamat supaya memuat ulang halaman tidak membuka modal lagi.
                if (window.location.search.includes('ubah=')) {
                    window.history.replaceState(null, '', window.location.pathname);
                }
            });
        </script>
    @endif
@endsection