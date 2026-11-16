@extends('layouts.app')

@section('title', 'Dashboard')

@section('subtitle')
    Ringkasan aktivitas dan akses portal Anda.
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    @if ($user->isStaff())
        <div class="max-w-4xl">
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
        </div>
    @elseif ($user->isApplicant())
        {{-- Kartu profil pelamar --}}
        <div class="card flex flex-wrap items-center justify-between gap-4 p-6">
            <div class="flex items-center gap-4">
                <x-avatar :name="$user->name" size="lg" />
                <div class="min-w-0">
                    <p class="font-semibold text-ink">{{ $user->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        {{ $education ? ($education->major . ' · ' . $education->institution_name) : 'Lengkapi identitas dan pendidikan pada lamaran' }}
                    </p>
                </div>
            </div>
            @if ($profileComplete)
                <x-tag variant="green">Profil lengkap</x-tag>
            @else
                <x-tag variant="neutral">Lengkapi profil</x-tag>
            @endif
        </div>

        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            <section aria-labelledby="apps-heading">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="apps-heading" class="text-xl font-semibold text-ink">Lamaran saya</h2>
                    <span class="text-sm text-muted">{{ $applicationCount }} lamaran</span>
                </div>

                @if ($applications->isEmpty())
                    <div class="card mt-4 p-6">
                        <p class="text-sm leading-6 text-muted">Anda belum memiliki lamaran. Pilih posisi magang pada katalog untuk memulai.</p>
                        <x-button href="{{ route('home') . '#posisi' }}" variant="primary" class="mt-5">Pilih posisi magang</x-button>
                    </div>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach ($applications->take(5) as $application)
                            @include('applications._card', ['application' => $application])
                        @endforeach
                    </div>
                    @if ($applicationCount > 5)
                        <x-button href="{{ route('applications.index') }}" class="mt-5">Lihat semua lamaran</x-button>
                    @endif
                @endif
            </section>

            <aside class="card p-5 lg:p-6" aria-labelledby="after-heading">
                <h3 id="after-heading" class="text-base font-semibold text-ink">Setelah mengirim lamaran</h3>
                <ol class="mt-4 space-y-4">
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-primary text-sm font-semibold text-primary">1</span>
                        <div>
                            <p class="font-semibold text-ink">Pemeriksaan berkas</p>
                            <p class="mt-0.5 text-sm text-muted">Admin memeriksa kelengkapan.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-primary text-sm font-semibold text-primary">2</span>
                        <div>
                            <p class="font-semibold text-ink">Seleksi unit</p>
                            <p class="mt-0.5 text-sm text-muted">Manager dan SM meninjau kesesuaian.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-primary text-sm font-semibold text-primary">3</span>
                        <div>
                            <p class="font-semibold text-ink">Pengumuman</p>
                            <p class="mt-0.5 text-sm text-muted">Hasil tersedia setelah admin mengumumkan.</p>
                        </div>
                    </li>
                </ol>
                <p class="mt-5 border-t border-line pt-4 text-sm text-muted">Hasil seleksi hanya ditampilkan di akun masing-masing peserta.</p>
            </aside>
        </div>
    @endif
</div>
@endsection