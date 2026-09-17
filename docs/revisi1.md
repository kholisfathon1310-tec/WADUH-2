# Penyempurnaan Sistem Reservasi Pemesan

Baca dan pahami seluruh isi file ini terlebih dahulu sebelum melakukan perubahan pada project. Saya ingin menyempurnakan sistem yang sudah ada menjadi **sistem reservasi yang profesional, modern, rapi, dan nyaman digunakan**, dengan fokus utama pada pengalaman Pemesan/User.

## Konsep Utama

Seluruh fitur yang berkaitan dengan **Pemesan hanya dapat diakses setelah user melakukan login**. User yang belum memiliki akun harus melakukan registrasi terlebih dahulu, kemudian login untuk masuk ke area Pemesan.

Setelah berhasil login, user diarahkan ke **Dashboard Pemesan**. Dari Dashboard tersebut, user dapat mengakses seluruh fitur yang memang diperuntukkan bagi Pemesan.

Proses login dan registrasi tetap dibuat normal dan profesional. Fokus penyederhanaan adalah pada **alur reservasi**, sehingga setelah login user dapat melakukan reservasi dengan langkah yang sederhana, jelas, dan tidak bertele-tele.

## Menu Pemesan

Gunakan struktur navigasi utama berikut:

**Dashboard | Fasilitas | Keranjang | Reservasi Saya | Akun**

Menu **Akun** berisi:

* Profil Saya
* Ubah Password
* Logout

Tidak perlu membuat menu utama terpisah untuk **Kategori, Jenis Sewa, Denah, atau Status Reservasi**. Fitur-fitur tersebut harus menjadi bagian dari konteks dan alur sistem yang sesuai.

## Dashboard Pemesan

Dashboard menjadi halaman utama setelah user berhasil login.

Dashboard harus memberikan gambaran singkat mengenai aktivitas Pemesan dan menyediakan akses cepat ke fitur penting. Tampilkan informasi yang relevan seperti ringkasan jumlah reservasi, reservasi terbaru, status reservasi yang sedang berjalan, akses menuju fasilitas, serta informasi penting lainnya jika memang tersedia dalam sistem.

Dashboard tidak perlu terlalu kompleks. Buat tampilannya profesional, informatif, dan membantu user melanjutkan aktivitas reservasi dengan cepat.

## Fasilitas

Menu **Fasilitas** menjadi tempat utama Pemesan untuk melihat fasilitas yang dapat dipesan.

Tampilkan fasilitas dengan tampilan yang rapi dan profesional. Setiap fasilitas dapat menampilkan gambar, nama, deskripsi singkat, informasi penting, serta tombol untuk melihat detail.

Pada **Detail Fasilitas**, tampilkan informasi yang memang dibutuhkan Pemesan sebelum melakukan reservasi.

Dari halaman detail tersebut, user harus dapat melanjutkan langsung ke proses reservasi.

## Kategori dan Jenis Sewa

**Kategori dan jenis sewa yang sudah ada harus tetap dipertahankan.**

Jangan menghapus, mengganti, atau membuat ulang data kategori maupun jenis sewa.

Kategori dan jenis sewa tidak perlu menjadi menu pada navbar. Keduanya ditampilkan sebagai bagian dari proses reservasi setelah user memilih fasilitas.

Buat cara pemilihannya mudah dipahami dan profesional, menggunakan UI yang sesuai dengan struktur sistem yang sudah ada.

## Denah

**Denah yang sudah ada harus tetap dipertahankan.**

Jangan mengubah struktur, posisi, pembagian area, nama area, maupun konsep denah yang sudah ditentukan.

Denah menjadi bagian dari proses reservasi sesuai dengan fungsi yang sudah ada pada sistem.

## Alur Reservasi

Fokus utama perubahan adalah membuat proses reservasi **sederhana, jelas, dan efisien setelah user login**.

Alur yang diharapkan:

**Login → Dashboard → Fasilitas → Detail Fasilitas → Pilih Kategori/Jenis Sewa → Tentukan Detail Reservasi → Tambah ke Keranjang → Keranjang → Konfirmasi Reservasi → Reservasi Berhasil**

Jangan membuat user melewati terlalu banyak halaman atau langkah yang tidak diperlukan.

User harus selalu mengetahui:

* fasilitas yang dipilih,
* kategori,
* jenis sewa,
* tanggal/periode,
* jumlah atau kebutuhan reservasi,
* harga dan total jika memang digunakan oleh sistem.

Sebelum reservasi diselesaikan, tampilkan **ringkasan reservasi** agar user dapat memeriksa kembali seluruh pilihannya sebelum melakukan konfirmasi.

## Keranjang

**Keranjang tetap menjadi fitur utama Pemesan.**

Setelah memilih fasilitas, kategori, jenis sewa, dan detail reservasi, user dapat menambahkannya ke Keranjang.

Keranjang harus menampilkan ringkasan pesanan dengan jelas, termasuk informasi yang memang digunakan oleh sistem seperti fasilitas, kategori, jenis sewa, tanggal/periode, jumlah, harga, subtotal, dan total.

Pertahankan fitur keranjang yang sudah ada, termasuk fungsi menambah, mengubah, atau menghapus item jika memang tersedia.

Buat pengalaman menggunakan keranjang lebih rapi dan mudah dipahami.

## Reservasi Saya

**Reservasi Saya menjadi pusat seluruh aktivitas reservasi Pemesan.**

User dapat melihat reservasi miliknya dan mengetahui status masing-masing reservasi.

Status tidak perlu dibuat sebagai menu terpisah. Status menjadi bagian dari **Reservasi Saya**.

Jika status yang tersedia dalam sistem mencakup beberapa kondisi, tampilkan sesuai dengan business logic yang sudah ada, misalnya:

* Menunggu
* Disetujui
* Ditolak
* Selesai
* Dibatalkan
Kadaluwarsa

Setiap reservasi harus menampilkan informasi penting seperti nomor reservasi, fasilitas, tanggal/periode, total, dan status.

User dapat membuka **Detail Reservasi** untuk melihat informasi lengkap serta status terbaru.

Pastikan user hanya dapat melihat dan mengakses reservasi miliknya sendiri.

## Akun

Menu **Akun** digunakan untuk mengelola akun Pemesan.

Sediakan:

* Profil Saya
* Data diri
* Email
* Nomor HP jika digunakan oleh sistem
* Ubah Password
* Logout

Logout harus tersedia dan berfungsi dengan baik.

## Authentication dan Authorization

Seluruh fitur Pemesan harus berada di balik authentication.

User yang belum login tidak boleh mengakses:

* Dashboard Pemesan
* Fasilitas
* Keranjang
* Reservasi Saya
* Profil
* maupun fitur Pemesan lainnya.

Pastikan user hanya dapat melihat dan mengelola data miliknya sendiri.

User tidak boleh mengakses halaman atau fitur Admin.

Authentication dan authorization harus diterapkan dengan aman menggunakan mekanisme yang sesuai dengan framework dan struktur project yang sudah ada.

## UI/UX

Seluruh area Pemesan harus memiliki tampilan yang **profesional, modern, bersih, konsisten, dan responsive**.

Jangan membuatnya terlihat seperti sekumpulan halaman CRUD.

Perhatikan dengan baik:

* Navbar
* Layout
* Typography
* Spacing
* Card
* Button
* Form
* Icon
* Modal
* Alert/notifikasi
* Loading state
* Empty state
* Error state
* Success state
* Responsive mobile

Gunakan identitas visual yang sudah ada jika masih sesuai, tetapi rapikan dan tingkatkan kualitas tampilannya agar lebih profesional sebagai sistem reservasi.

## Ketentuan yang Tidak Boleh Diubah

Pertahankan sepenuhnya:

* Denah yang sudah ada
* Kategori yang sudah ada
* Jenis sewa yang sudah ada
* Data existing
* Business logic utama
* Fitur Admin yang sudah berjalan
* Fitur Keranjang yang sudah ada

Jangan menghapus fitur yang sudah tersedia hanya untuk menyederhanakan tampilan.

Yang perlu disederhanakan adalah **alur interaksi Pemesan ketika melakukan reservasi**, bukan mengurangi fitur penting dari sistem.

Jangan mengubah database atau menambahkan library baru jika tidak benar-benar diperlukan.

## Cara Mengerjakan

Sebelum melakukan coding, **analisis terlebih dahulu project yang ada**. Pahami struktur folder, framework, database, route, controller, model, view/component, authentication, middleware, serta alur reservasi yang sudah berjalan.

Identifikasi bagian yang perlu diperbaiki dan gunakan struktur serta teknologi yang sudah digunakan oleh project.

Jangan langsung melakukan perubahan besar tanpa memahami kode yang sudah ada. Hindari merusak fitur existing.

Setelah memahami project, implementasikan perubahan secara bertahap dan pastikan fitur Admin tetap berjalan normal.

## Target Akhir

Pengalaman Pemesan yang diinginkan adalah:

**Registrasi → Login → Dashboard → Fasilitas → Pilih Fasilitas → Lihat Detail → Pilih Kategori/Jenis Sewa → Tentukan Detail Reservasi → Tambah ke Keranjang → Periksa Keranjang → Konfirmasi → Reservasi Berhasil → Reservasi Saya → Lihat Status dan Detail Reservasi**

Hasil akhirnya harus terasa seperti **sistem reservasi profesional yang benar-benar siap digunakan**, dengan navigasi yang jelas, tampilan yang rapi, proses reservasi yang sederhana, serta tetap mempertahankan denah, kategori, jenis sewa, keranjang, business logic, dan fitur Admin yang sudah ada.
