<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Seluruh filter & pencarian GET di aplikasi ini bernilai tunggal (teks/angka). Nilai
 * berbentuk array dari URL yang diutak-atik (mis. ?pemesan[]=x) dibuang di sini, supaya
 * tidak memicu galat 500 saat controller memperlakukannya sebagai teks.
 */
class AbaikanQueryArray
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $query = $request->query->all();
            $bersih = array_filter($query, fn ($nilai) => ! is_array($nilai));
            if (count($bersih) !== count($query)) {
                $request->query->replace($bersih);
            }
        }

        return $next($request);
    }
}
