<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ForgotPasswordController;
use App\Http\Controllers\Admin\FakturController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\ReservasiAdminController;
use App\Http\Controllers\BuktiReservasiController;
use App\Http\Controllers\CekStatusController;
use App\Http\Controllers\Customer\AkunController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Customer\ReservasiSayaController;
use App\Http\Controllers\FasilitasController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReservasiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', HomeController::class)->name('home');

/*
| Alur Pemesan — Revisi: seluruh alur booking kini wajib login (guard `customer`).
| Pengecualian: `batalkan` tetap publik karena juga dipakai halaman "Cek Status" (guest).
| Catatan urutan: route dengan segmen literal (fasilitas, keranjang, checkout) didaftarkan
| sebelum route dinamis {kategori} agar tidak "tertelan" oleh parameter kategori.
*/
Route::prefix('reservasi')->name('reservasi.')->group(function () {
    Route::post('/{kode_reservasi}/batalkan', [ReservasiController::class, 'batalkan'])->name('batalkan');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [ReservasiController::class, 'index'])->name('index');

        Route::get('/checkout', [ReservasiController::class, 'checkoutForm'])->name('checkout.form');
        Route::post('/checkout', [ReservasiController::class, 'checkout'])->name('checkout');
        Route::get('/sukses', [ReservasiController::class, 'sukses'])->name('sukses');

        Route::get('/fasilitas/{fasilitas}', [ReservasiController::class, 'fasilitas'])->name('fasilitas.show');
        Route::get('/fasilitas/{fasilitas}/jam-terisi', [ReservasiController::class, 'jamTerisi'])->name('fasilitas.jam-terisi');

        Route::post('/keranjang', [ReservasiController::class, 'tambahKeranjang'])->name('keranjang.tambah');
        Route::delete('/keranjang', [ReservasiController::class, 'kosongkanKeranjang'])->name('keranjang.kosongkan');
        Route::delete('/keranjang/{index}', [ReservasiController::class, 'hapusKeranjang'])->name('keranjang.hapus');

        Route::get('/{kategori}/jenis-sewa', [ReservasiController::class, 'jenisSewa'])->name('jenis-sewa');
        Route::get('/{kategori}/lantai', [ReservasiController::class, 'lantai'])->name('lantai');
        Route::get('/{kategori}/denah/{lantai}', [ReservasiController::class, 'denah'])->name('denah');
    });
});

/*
| Jelajah Fasilitas (publik, tanpa auth) — TERPISAH dari alur reservasi di atas.
| Murni informasi (denah + detail), tidak ada jalan ke keranjang/checkout.
*/
Route::prefix('fasilitas')->name('fasilitas.')->group(function () {
    Route::get('/', [FasilitasController::class, 'index'])->name('index');
    Route::get('/detail/{fasilitas}', [FasilitasController::class, 'detail'])->name('detail');
    Route::get('/{kategori}/lantai', [FasilitasController::class, 'lantai'])->name('lantai');
    Route::get('/{kategori}/denah/{lantai}', [FasilitasController::class, 'denah'])->name('denah');
});

Route::get('/cek-status', [CekStatusController::class, 'form'])->name('cek-status.form');
Route::post('/cek-status', [CekStatusController::class, 'cari'])->name('cek-status.cari');
// GET dengan kode di URL — stabil (bisa di-bookmark/refresh) supaya setelah aksi seperti
// "Batalkan" bisa redirect balik ke sini, bukan keluar ke form kosong.
Route::get('/cek-status/{kode}', [CekStatusController::class, 'hasil'])->name('cek-status.hasil');
Route::get('/cek-status/{kodeReservasi}/bukti-reservasi', [BuktiReservasiController::class, 'unduh'])->name('cek-status.bukti-reservasi');

// Preview denah gedung BITC (visualisasi 5 lantai). Tidak melakukan reservasi.
Route::view('/denah-preview', 'denah-preview')->name('denah-preview');

/*
| Alur Pemesan berlogin — Revisi. Guard `customer` (tabel pemesan, model App\Models\Pemesan).
*/
Route::prefix('customer')->name('customer.')->group(function () {
    // Registrasi & login (hanya untuk yang belum login sebagai Pemesan).
    Route::middleware('guest:customer')->group(function () {
        Route::get('/daftar', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/daftar', [CustomerAuthController::class, 'register'])->name('register.attempt');
        Route::get('/masuk', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/masuk', [CustomerAuthController::class, 'login'])->name('login.attempt');

        // Lupa kata sandi.
        Route::get('/lupa-password', [CustomerForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/lupa-password', [CustomerForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [CustomerForgotPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [CustomerForgotPasswordController::class, 'reset'])->name('password.update');
    });

    // Area terproteksi.
    Route::middleware(['auth:customer', 'cache.headers:no_store'])->group(function () {
        Route::post('/keluar', [CustomerAuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', CustomerDashboardController::class)->name('dashboard');

        Route::get('/reservasi-saya', [ReservasiSayaController::class, 'index'])->name('reservasi-saya.index');
        Route::get('/reservasi-saya/{kode}', [ReservasiSayaController::class, 'show'])->name('reservasi-saya.show');

        Route::get('/profil', [AkunController::class, 'profil'])->name('akun.profil');
        Route::put('/profil', [AkunController::class, 'updateProfil'])->name('akun.profil.update');
        Route::get('/profil/password', [AkunController::class, 'password'])->name('akun.password');
        Route::put('/profil/password', [AkunController::class, 'updatePassword'])->name('akun.password.update');
    });
});

/*
| Alur Admin — Stage 3. Guard `admin` (tabel admin terpisah dari users).
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Login (hanya untuk yang belum login sebagai admin).
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');

        // Lupa kata sandi.
        Route::get('/lupa-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/lupa-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
    });

    // Area terproteksi. cache.headers:no_store mencegah browser menyimpan halaman ini di
    // cache/bfcache, supaya tombol "Kembali" selalu memuat ulang dari server (data terbaru,
    // bukan snapshot lama yang bisa masih menampilkan modal konfirmasi yang belum tertutup).
    Route::middleware(['auth:admin', 'cache.headers:no_store'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
        Route::get('/monitoring/fasilitas/{fasilitas}', [MonitoringController::class, 'detail'])->name('monitoring.detail');

        // Data Reservasi + verifikasi/persetujuan.
        Route::get('/reservasi', [ReservasiAdminController::class, 'index'])->name('reservasi.index');
        Route::get('/reservasi/{kodeReservasi}', [ReservasiAdminController::class, 'show'])->name('reservasi.show');
        Route::post('/reservasi/{kodeReservasi}/setujui', [ReservasiAdminController::class, 'setujui'])->name('reservasi.setujui');
        Route::post('/reservasi/{kodeReservasi}/tolak', [ReservasiAdminController::class, 'tolak'])->name('reservasi.tolak');
        Route::delete('/reservasi/{kodeReservasi}', [ReservasiAdminController::class, 'hapus'])->name('reservasi.hapus');
        Route::post('/dokumen/{dokumen}/verifikasi', [ReservasiAdminController::class, 'verifikasiDokumen'])->name('reservasi.dokumen.verifikasi');

        // Faktur — SATU faktur per kode reservasi/transaksi (multi-ruangan digabung 1 PDF).
        Route::post('/reservasi/{kodeReservasi}/faktur', [FakturController::class, 'cetak'])->name('reservasi.faktur.cetak');
        Route::get('/reservasi/{kodeReservasi}/faktur', [FakturController::class, 'unduh'])->name('reservasi.faktur.unduh');

        // Laporan.
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
        Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');

        // Profil admin (data akun, ubah email/kata sandi).
        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
        Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
        Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');
    });
});