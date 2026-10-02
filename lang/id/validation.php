<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bahasa Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Terjemahan resmi ke Bahasa Indonesia untuk pesan validasi bawaan Laravel.
    | Disusun singkat dan langsung ke inti (bukan terjemahan literal per kata)
    | supaya terasa seperti sistem profesional, bukan hasil translate otomatis.
    |
    */

    'accepted'             => ':attribute wajib disetujui.',
    'accepted_if'          => ':attribute wajib disetujui saat :other bernilai :value.',
    'active_url'           => ':attribute bukan URL yang valid.',
    'after'                => ':attribute harus setelah :date.',
    'after_or_equal'       => ':attribute harus sama dengan atau setelah :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa daftar.',
    'before'               => ':attribute harus sebelum :date.',
    'before_or_equal'      => ':attribute harus sama dengan atau sebelum :date.',
    'between'              => [
        'array'   => ':attribute harus berjumlah :min–:max item.',
        'file'    => ':attribute harus berukuran :min–:max kilobyte.',
        'numeric' => ':attribute harus di antara :min–:max.',
        'string'  => ':attribute harus :min–:max karakter.',
    ],
    'boolean'              => ':attribute harus bernilai benar atau salah.',
    'confirmed'            => 'Konfirmasi :attribute tidak cocok.',
    'current_password'     => 'Kata sandi salah.',
    'date'                 => ':attribute tidak valid.',
    'date_equals'          => ':attribute harus tanggal yang sama dengan :date.',
    'date_format'          => 'Format :attribute tidak valid.',
    'decimal'              => ':attribute harus memiliki :decimal angka desimal.',
    'declined'             => ':attribute wajib ditolak.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus terdiri dari :digits digit.',
    'digits_between'       => ':attribute harus terdiri dari :min–:max digit.',
    'distinct'             => ':attribute memiliki nilai yang sama, tidak boleh duplikat.',
    'email'                => 'Format :attribute tidak valid.',
    'ends_with'            => ':attribute harus diakhiri dengan salah satu dari: :values.',
    'enum'                 => ':attribute yang dipilih tidak valid.',
    'exists'               => ':attribute yang dipilih tidak tersedia.',
    'file'                 => ':attribute harus berupa berkas.',
    'filled'               => ':attribute wajib diisi.',
    'gt'                   => [
        'array'   => ':attribute harus lebih dari :value item.',
        'file'    => ':attribute harus lebih besar dari :value kilobyte.',
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string'  => ':attribute harus lebih dari :value karakter.',
    ],
    'gte'                  => [
        'array'   => ':attribute harus :value item atau lebih.',
        'file'    => ':attribute harus :value kilobyte atau lebih besar.',
        'numeric' => ':attribute harus :value atau lebih besar.',
        'string'  => ':attribute harus :value karakter atau lebih.',
    ],
    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak valid.',
    'in_array'             => ':attribute tidak ada dalam :other.',
    'integer'              => ':attribute harus berupa angka bulat.',
    'ip'                   => ':attribute harus berupa alamat IP yang valid.',
    'ipv4'                 => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6'                 => ':attribute harus berupa alamat IPv6 yang valid.',
    'json'                 => ':attribute harus berupa teks JSON yang valid.',
    'lowercase'            => ':attribute harus huruf kecil.',
    'lt'                   => [
        'array'   => ':attribute harus kurang dari :value item.',
        'file'    => ':attribute harus lebih kecil dari :value kilobyte.',
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string'  => ':attribute harus kurang dari :value karakter.',
    ],
    'lte'                  => [
        'array'   => ':attribute tidak boleh lebih dari :value item.',
        'file'    => ':attribute harus :value kilobyte atau lebih kecil.',
        'numeric' => ':attribute harus :value atau lebih kecil.',
        'string'  => ':attribute harus :value karakter atau kurang.',
    ],
    'mac_address'          => ':attribute harus berupa alamat MAC yang valid.',
    'max'                  => [
        'array'   => ':attribute maksimal :max item.',
        'file'    => 'Ukuran :attribute maksimal :max kilobyte.',
        'numeric' => ':attribute maksimal :max.',
        'string'  => ':attribute maksimal :max karakter.',
    ],
    'max_digits'           => ':attribute maksimal :max digit.',
    'mimes'                => ':attribute harus berformat: :values.',
    'mimetypes'            => ':attribute harus berformat: :values.',
    'min'                  => [
        'array'   => ':attribute minimal :min item.',
        'file'    => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string'  => ':attribute minimal :min karakter.',
    ],
    'min_digits'           => ':attribute minimal :min digit.',
    'not_in'               => ':attribute yang dipilih tidak valid.',
    'not_regex'            => 'Format :attribute tidak valid.',
    'numeric'              => ':attribute harus berupa angka.',
    'password'             => [
        'letters'       => ':attribute harus mengandung minimal satu huruf.',
        'mixed'         => ':attribute harus mengandung huruf besar dan kecil.',
        'numbers'       => ':attribute harus mengandung minimal satu angka.',
        'symbols'       => ':attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute ini pernah tercantum dalam kebocoran data. Gunakan :attribute yang berbeda.',
    ],
    'present'              => ':attribute wajib ada.',
    'prohibited'           => ':attribute tidak diperbolehkan.',
    'prohibited_if'        => ':attribute tidak diperbolehkan saat :other bernilai :value.',
    'prohibited_unless'    => ':attribute tidak diperbolehkan kecuali :other ada di :values.',
    'prohibits'            => ':attribute tidak boleh diisi bersama :other.',
    'regex'                => 'Format :attribute tidak valid.',
    'required'             => ':attribute wajib diisi.',
    'required_array_keys'  => ':attribute wajib memiliki entri untuk: :values.',
    'required_if'          => ':attribute wajib diisi saat :other bernilai :value.',
    'required_if_accepted' => ':attribute wajib diisi saat :other disetujui.',
    'required_unless'      => ':attribute wajib diisi kecuali :other ada di :values.',
    'required_with'        => ':attribute wajib diisi jika :values diisi.',
    'required_with_all'    => ':attribute wajib diisi jika :values diisi.',
    'required_without'     => ':attribute wajib diisi jika :values tidak diisi.',
    'required_without_all' => ':attribute wajib diisi jika semua dari :values tidak diisi.',
    'same'                 => ':attribute dan :other harus sama.',
    'size'                 => [
        'array'   => ':attribute harus berjumlah :size item.',
        'file'    => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => ':attribute harus :size.',
        'string'  => ':attribute harus :size karakter.',
    ],
    'starts_with'          => ':attribute harus diawali dengan salah satu dari: :values.',
    'string'               => ':attribute harus berupa teks.',
    'unique'               => ':attribute sudah terdaftar. Gunakan :attribute lain.',
    'uploaded'             => ':attribute gagal diunggah.',
    'uppercase'            => ':attribute harus huruf besar.',
    'url'                  => 'Format :attribute tidak valid.',
    'uuid'                 => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Kustom per Field/Aturan
    |--------------------------------------------------------------------------
    |
    | Field yang perlu pesan lebih spesifik dari terjemahan generik di atas.
    |
    */

    'custom' => [
        // Identitas — dipakai bersama form pemesan & admin (lihat App\Support\AturanKolom).
        'nama_lengkap' => ['regex' => 'Nama lengkap hanya boleh berisi huruf.'],
        'nama_admin'   => ['regex' => 'Nama hanya boleh berisi huruf.'],
        'email'        => ['email' => 'Format email tidak valid, contoh: nama@email.com.'],
        'admin_email'  => ['email' => 'Format email tidak valid, contoh: nama@email.com.'],
        'no_telepon'   => ['regex' => 'No. telepon harus berupa angka 10–15 digit.'],
        'no_whatsapp'  => ['regex' => 'No. WhatsApp harus berupa angka 10–15 digit.'],
        'usia'         => ['integer' => 'Usia harus berupa angka.', 'min' => 'Usia minimal :min tahun.', 'max' => 'Usia maksimal :max tahun.'],
        'pekerjaan'    => ['regex' => 'Pekerjaan hanya boleh berisi huruf.'],

        // Kata sandi.
        'password'      => ['confirmed' => 'Konfirmasi kata sandi tidak cocok.'],
        'password_baru' => ['confirmed' => 'Konfirmasi kata sandi baru tidak cocok.'],

        // Reservasi.
        'tanggal_mulai'   => ['after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.'],
        'jumlah_pengguna' => ['integer' => 'Jumlah pengguna harus berupa angka.', 'min' => 'Jumlah pengguna minimal :min orang.'],
        'kode'            => ['regex' => 'Format kode reservasi tidak valid, contoh: RSV-7K3M.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Field (Attributes)
    |--------------------------------------------------------------------------
    |
    | Supaya pesan validasi menyebut nama field yang manusiawi, bukan nama
    | kolom database mentah (mis. "no_whatsapp" -> "No. WhatsApp").
    |
    */

    'attributes' => [
        'nama_admin'          => 'Nama',
        'nama_lengkap'        => 'Nama lengkap',
        'email'               => 'Email',
        'no_whatsapp'         => 'No. WhatsApp',
        'no_telepon'          => 'No. telepon',
        'alamat'              => 'Alamat',
        'usia'                => 'Usia',
        'pekerjaan'           => 'Pekerjaan',
        'password'            => 'Kata sandi',
        'password_lama'       => 'Kata sandi lama',
        'password_baru'       => 'Kata sandi baru',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'password_baru_confirmation' => 'Konfirmasi kata sandi baru',
        'admin_email'         => 'Email',
        'admin_password'      => 'Kata sandi',
        'kode'                => 'Kode reservasi',
        'tanggal'             => 'Tanggal',
        'id_fasilitas'        => 'Fasilitas',
        'id_tarif_sewa'       => 'Tarif sewa',
        'status_verifikasi'   => 'Status verifikasi',
        'keperluan'           => 'Keperluan',
        'tanggal_mulai'       => 'Tanggal mulai',
        'tanggal_selesai'     => 'Tanggal selesai',
        'jam_mulai'           => 'Jam mulai',
        'jam_selesai'         => 'Jam selesai',
        'dokumen'             => 'Dokumen',
        'jumlah_pengguna'     => 'Jumlah pengguna',
        'foto'                => 'Foto',
        'alasan'              => 'Alasan',
        'bulan'               => 'Bulan',
        'tahun'               => 'Tahun',
        'status'              => 'Status',
        'kategori'            => 'Kategori',
        'kode_reservasi'      => 'Kode reservasi',
    ],

];
