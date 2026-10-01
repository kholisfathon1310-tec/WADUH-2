<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode Verifikasi Email</title>
</head>
<body style="margin:0; padding:0; background:#f4f7f9; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f9; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background:#176b87; padding:20px 28px; color:#ffffff;">
                            <div style="font-size:18px; font-weight:bold;">WADUH</div>
                            <div style="font-size:12px; opacity:.85;">Sistem Reservasi Fasilitas BITC Cimahi</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px; font-size:15px; font-weight:bold;">Kode Verifikasi Email</p>
                            <p style="margin:0 0 20px; font-size:14px; line-height:1.6; color:#475569;">
                                Gunakan kode berikut untuk {{ $keperluan }}.
                            </p>
                            <div style="text-align:center; margin:0 0 20px;">
                                <span style="display:inline-block; font-size:32px; font-weight:bold; letter-spacing:10px; color:#0f526b; background:#e6f2f4; border:1px solid #c9e6ea; border-radius:12px; padding:14px 22px;">{{ $kode }}</span>
                            </div>
                            <p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#475569;">
                                Kode berlaku selama <strong>{{ $berlakuMenit }} menit</strong> dan hanya dapat digunakan satu kali.
                                Jangan berikan kode ini kepada siapa pun, termasuk petugas BITC.
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px; background:#f8fafc; border-top:1px solid #eef2f6; font-size:11px; color:#94a3b8;">
                            Email ini dikirim otomatis oleh sistem WADUH &middot; BITC Cimahi. Mohon tidak membalas email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
