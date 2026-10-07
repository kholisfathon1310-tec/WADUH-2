/* WADUH — tema popup terpusat. Membungkus Swal.fire() supaya SEMUA popup (konfirmasi,
   berhasil, gagal) memakai tampilan yang sama, tanpa mengubah teks di tiap halaman. */
(function () {
    if (!window.Swal || Swal.__temaWaduh) return;
    var asli = Swal.fire.bind(Swal);
    var ikon = { success: 'bi-check-lg', error: 'bi-x-lg', warning: 'bi-exclamation-lg', info: 'bi-info-lg', question: 'bi-question-lg' };

    // Warna tombol yang sudah ditentukan halaman (mis. merah untuk Hapus) dipetakan ke varian tema.
    var varian = function (warna) {
        warna = String(warna || '').toLowerCase();
        if (/e11d48|dc3545|d33|ef4444|be123c/.test(warna)) return 'danger';
        if (/25b47e|198754|10b981|16a34a|047857/.test(warna)) return 'success';
        return 'primary';
    };

    Swal.fire = function () {
        var a = arguments;
        var o = (a[0] && typeof a[0] === 'object') ? Object.assign({}, a[0]) : { title: a[0], html: a[1], icon: a[2] };
        var jenis = ikon[o.icon] ? o.icon : 'info';
        var kelas = {
            popup: 'wp-popup wp-' + jenis,
            icon: 'wp-icon',
            title: 'wp-title',
            htmlContainer: 'wp-text',
            actions: 'wp-actions',
            confirmButton: 'wp-btn wp-btn-' + varian(o.confirmButtonColor),
            cancelButton: 'wp-btn wp-btn-batal',
            denyButton: 'wp-btn wp-btn-batal',
        };
        o.customClass = Object.assign(kelas, o.customClass || {});
        o.buttonsStyling = false;
        if (o.icon && !o.iconHtml) o.iconHtml = '<i class="bi ' + ikon[jenis] + '"></i>';
        // Tombol Batal di kiri, aksi utama di kanan.
        if (o.showCancelButton && o.reverseButtons === undefined) o.reverseButtons = true;
        if (!o.showClass) o.showClass = { popup: 'wp-masuk', backdrop: 'swal2-backdrop-show', icon: 'swal2-icon-show' };
        if (!o.hideClass) o.hideClass = { popup: 'wp-keluar', backdrop: 'swal2-backdrop-hide', icon: 'swal2-icon-hide' };
        return asli(o);
    };
    Swal.__temaWaduh = true;
})();

/* Pesan validasi dari server: tandai kolomnya dengan garis merah (sama seperti validasi klien)
   dan hapus tanda itu begitu kolom diperbaiki. */
(function () {
    var PESAN = '.catatan-salah, .fl-err, .aj-field-err, .ck-edit-err';
    var kolomDari = function (pesan) {
        for (var n = pesan.previousElementSibling; n; n = n.previousElementSibling) {
            if (n.matches(PESAN)) continue;
            if (n.matches('input, select, textarea')) return n;
            return n.querySelector('input:not([type=hidden]):not([type=checkbox]), select, textarea, .jam-btn');
        }
        return null;
    };
    document.querySelectorAll(PESAN).forEach(function (pesan) {
        var kolom = kolomDari(pesan);
        if (!kolom || kolom.classList.contains('is-salah')) return;
        kolom.classList.add('is-salah', 'tanpa-goyang');
        var bersihkan = function () {
            kolom.removeEventListener('input', bersihkan);
            kolom.removeEventListener('change', bersihkan);
            if (window.WaduhValidasi) return window.WaduhValidasi.bersihkan(kolom);
            kolom.classList.remove('is-salah', 'tanpa-goyang');
            if (pesan.parentElement) pesan.remove();
        };
        kolom.addEventListener('input', bersihkan);
        kolom.addEventListener('change', bersihkan);
    });
})();
