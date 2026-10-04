@extends('layouts.public')

@section('title', 'Daftar sebagai pelamar')

@section('content')
<section aria-labelledby="register-heading" class="mx-auto max-w-md px-4 pt-10 sm:pt-16">
    <div class="card p-6 sm:p-8">
        <h1 id="register-heading" class="text-2xl font-semibold leading-tight">Buat akun pelamar</h1>
        <p class="mt-3 text-sm text-muted">Kolom bertanda <span aria-hidden="true">*</span> wajib diisi.</p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-card bg-primary-soft p-4 text-sm text-primary-hover">
                <p class="font-semibold">Akun belum dibuat.</p>
                <p class="mt-1">Perbaiki kolom yang ditandai, lalu kirim kembali formulir ini.</p>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="name" class="mb-2 block text-sm font-medium">Nama lengkap <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="160" required
                    class="form-input" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    @if ($errors->has('name')) aria-describedby="name-error" @endif>
                @error('name')
                    <p id="name-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Isi nama lengkap Anda, maksimal 160 karakter.</p>
                @enderror
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" maxlength="254" required
                    class="form-input" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    @if ($errors->has('email')) aria-describedby="email-error" @endif>
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Gunakan alamat email yang valid dan belum terdaftar, atau masuk jika sudah memiliki akun.</p>
                @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium">Kata sandi <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required
                    class="form-input" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    aria-describedby="password-hint{{ $errors->has('password') ? ' password-error' : '' }}">
                <p id="password-hint" class="mt-2 text-xs text-muted">Gunakan 8–72 karakter dan ulangi kata sandi yang sama di bawah.</p>
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Isi ulang kedua kolom kata sandi sesuai ketentuan di atas.</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium">Konfirmasi kata sandi <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required
                    class="form-input" aria-invalid="{{ $errors->hasAny(['password', 'password_confirmation']) ? 'true' : 'false' }}"
                    @if ($errors->hasAny(['password', 'password_confirmation'])) aria-describedby="password-confirmation-error" @endif>
                @if ($errors->hasAny(['password', 'password_confirmation']))
                    <p id="password-confirmation-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first('password_confirmation') ?: $errors->first('password') }} Ketik kembali kata sandi yang sama persis.</p>
                @endif
            </div>
            <x-button type="submit" variant="primary" full>Daftar sebagai pelamar</x-button>
        </form>
        <p class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-primary underline underline-offset-4 hover:text-primary-hover">Sudah punya akun? Masuk</a>
        </p>
    </div>
</section>
@endsection
