@extends('layouts.public')

@section('title', 'Akses tidak diizinkan')

@section('content')
<div class="mx-auto max-w-3xl px-6 py-16 sm:py-24">
    <section class="card p-6 sm:p-10" aria-labelledby="access-heading">
        <x-tag variant="neutral">Kesalahan 403</x-tag>
        <h1 id="access-heading" class="mt-6 text-3xl font-semibold leading-tight md:text-4xl">Akses tidak diizinkan</h1>
        <p class="mt-4 max-w-prose text-sm leading-6 text-muted">Akun Anda tidak memiliki kewenangan untuk membuka halaman atau melakukan tindakan ini. Jika Anda memerlukan akses, hubungi admin untuk memeriksa penugasan Anda.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            @auth
                <x-button :href="route('dashboard')" variant="primary">Kembali ke dashboard</x-button>
            @endauth
            <x-button :href="route('home')">Ke beranda</x-button>
        </div>
    </section>
</div>
@endsection
