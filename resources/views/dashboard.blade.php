@extends('layouts.public')

@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-4xl px-6 pt-10 sm:pt-16">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-5">
        <h1 class="min-w-0 break-words text-3xl font-semibold leading-tight">Halo, {{ $user->name }}</h1>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <x-button type="submit">Keluar</x-button>
        </form>
    </div>

    @if ($user->isStaff())
        <section aria-labelledby="staff-heading" class="card p-6 sm:p-8">
            <h2 id="staff-heading" class="text-2xl font-semibold">Dashboard Staf</h2>
            <h3 class="mt-6 text-sm font-medium text-muted">Penugasan aktif Anda</h3>
            <ul class="mt-3 divide-y divide-line">
                @foreach ($assignments as $assignment)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <x-tag>{{ $assignment->role_code }}</x-tag>
                        <span class="min-w-0 break-words text-sm">{{ $assignment->orgUnit?->name ?? 'Seluruh unit organisasi' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
        <section aria-labelledby="staff-modules-heading" class="card mt-5 p-6 sm:p-8">
            <x-icon name="clipboard" :size="28" class="mb-4 text-muted" />
            <h2 id="staff-modules-heading" class="text-lg font-semibold">Panel staf</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Kelola dan tinjau lamaran sesuai penugasan serta kewenangan Anda.</p>
            <x-button :href="route('staff.dashboard')" variant="primary" class="mt-6">Buka panel staf</x-button>
        </section>
    @elseif ($user->isApplicant())
        <section aria-labelledby="applicant-heading" class="card p-6 sm:p-8">
            <h2 id="applicant-heading" class="text-2xl font-semibold">Dashboard Pelamar</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Lihat posisi dan persyaratan magang pada katalog.</p>
            <div class="mt-6">
                <x-button href="/">Lihat posisi magang</x-button>
            </div>
        </section>
        <section aria-labelledby="applications-heading" class="card mt-5 p-6 sm:p-8">
            <x-icon name="clipboard" :size="28" class="mb-4 text-muted" />
            <h2 id="applications-heading" class="text-lg font-semibold">Lamaran saya</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Lanjutkan draf yang belum selesai dan pantau status lamaran yang sudah dikirim.</p>
            <x-button :href="route('applications.index')" variant="primary" class="mt-6">Lamaran saya</x-button>
        </section>
    @endif
</div>
@endsection
