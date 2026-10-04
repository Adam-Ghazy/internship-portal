@extends('layouts.public')

@section('title', 'Masuk')

@section('content')
<section aria-labelledby="login-heading" class="mx-auto max-w-md px-4 pt-10 sm:pt-16">
    <div class="card p-6 sm:p-8">
        <h1 id="login-heading" class="text-2xl font-semibold leading-tight">Masuk ke akun Anda</h1>
        <p class="mt-3 text-sm text-muted">Kolom bertanda <span aria-hidden="true">*</span> wajib diisi.</p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-card bg-primary-soft p-4 text-sm text-primary-hover">
                <p class="font-semibold">Email atau kata sandi salah.</p>
                <p class="mt-1">Periksa email dan kata sandi Anda, lalu coba masuk kembali.</p>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                    class="form-input" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    @if ($errors->has('email')) aria-describedby="email-error" @endif>
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Periksa kembali email yang Anda masukkan.</p>
                @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium">Kata sandi <span aria-hidden="true" class="text-primary">*</span></label>
                <input id="password" type="password" name="password" autocomplete="current-password" required
                    class="form-input" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    @if ($errors->has('password')) aria-describedby="password-error" @endif>
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Masukkan kata sandi akun Anda.</p>
                @enderror
            </div>
            <div>
                <label for="remember" class="inline-flex min-h-[46px] cursor-pointer items-center gap-3 text-sm">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))
                        class="h-5 w-5 rounded border-line text-primary focus:ring-primary" aria-describedby="remember-hint">
                    Ingat saya
                </label>
                <p id="remember-hint" class="text-xs text-muted">Gunakan hanya pada perangkat pribadi.</p>
            </div>
            <x-button type="submit" variant="primary" full>Masuk</x-button>
        </form>
        <p class="mt-6 text-center text-sm">
            <a href="{{ route('register') }}" class="font-medium text-primary underline underline-offset-4 hover:text-primary-hover">Belum punya akun? Daftar</a>
        </p>
    </div>
</section>
@endsection
