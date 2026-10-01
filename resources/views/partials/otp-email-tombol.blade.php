{{--
    Tombol "Kirim OTP" + tanda centang "Terverifikasi" yang menempel DI DALAM kolom email.
    Letakkan di dalam pembungkus kolom email yang diberi kelas `otp-field`.
    Variabel: $emailId (id kolom email).
--}}
<button type="button" class="otp-kirim" data-otp-kirim="{{ $emailId }}">
    <i class="bi bi-send"></i><span>Kirim OTP</span>
</button>
<span class="otp-badge" data-otp-badge="{{ $emailId }}" hidden><i class="bi bi-patch-check-fill"></i>Terverifikasi</span>
