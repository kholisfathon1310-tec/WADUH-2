<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form ber-atribut data-nav-replace (setujui/tolak reservasi) dikirim lewat fetch, lalu
 * halaman diganti dengan location.replace(). Fetch otomatis mengikuti redirect, sehingga
 * permintaan GET hasil redirect itu ikut "memakai" pesan flash (sukses/galat/validasi) dan
 * halaman yang akhirnya dimuat tidak menampilkan notifikasi apa pun.
 *
 * Permintaan dari fetch tersebut membawa header X-Nav-Replace; di sini flash-nya
 * dipertahankan satu permintaan lagi supaya tampil pada halaman hasil location.replace().
 */
class PertahankanFlashNavReplace
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->headers->has('X-Nav-Replace') && $request->hasSession()) {
            $request->session()->reflash();
        }

        return $response;
    }
}
