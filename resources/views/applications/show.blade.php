@extends('layouts.public')

@section('title', 'Detail lamaran')

@section('content')
@php
    $submittedDate = null;
    if (! empty($submission->submitted_at)) {
        try {
            $submittedDate = \Illuminate\Support\Carbon::parse($submission->submitted_at)->locale('id')->translatedFormat('d M Y, H:i');
        } catch (\Throwable $exception) {
            $submittedDate = null;
        }
    }
@endphp
<div class="mx-auto max-w-4xl px-6 py-12">
    <a href="{{ route('applications.index') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke lamaran saya</a>

    @if (session('status'))
        <div role="status" class="mt-6 rounded-card border border-line bg-surface p-4 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-card border border-primary bg-primary-soft p-4 text-sm text-primary-hover">
            <h2 class="font-semibold">Lamaran belum dapat diproses</h2>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="mt-8">
        <h1 class="text-3xl font-semibold leading-tight md:text-4xl">Detail lamaran</h1>
        <p class="mt-3 max-w-prose text-sm leading-6 text-muted">Data berikut adalah salinan lamaran saat dikirim. Data pada halaman ini tidak dapat diubah.</p>
        <div class="mt-5 flex flex-wrap items-center gap-3">
            @if ($application->stage === 'submitted')
                <span class="tag bg-primary text-white">{{ $application->stage }}</span>
            @elseif ($application->stage === 'needs_revision')
                <x-tag>{{ $application->stage }}</x-tag>
            @else
                <x-tag variant="neutral">{{ $application->stage }}</x-tag>
            @endif
            <span class="break-all text-sm">Referensi: <strong>{{ $application->public_reference ?? 'Tidak tersedia' }}</strong></span>
        </div>
        <dl class="mt-6 grid gap-4 border-y border-line py-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted">Tanggal dikirim</dt>
                <dd class="mt-1 font-medium tabular-nums">{{ $submittedDate ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Versi pengiriman</dt>
                <dd class="mt-1 font-medium tabular-nums">{{ $submission->version_no }}</dd>
            </div>
            @if ($application->stage === 'withdrawn')
                <div>
                    <dt class="text-muted">Tanggal ditarik</dt>
                    <dd class="mt-1 font-medium tabular-nums">{{ \Illuminate\Support\Carbon::parse($application->withdrawn_at)->locale('id')->translatedFormat('d M Y, H:i') }}</dd>
                </div>
            @endif
        </dl>
        @if ($application->stage === 'withdrawn')
            <p class="mt-4 text-sm leading-6 text-muted">Lamaran ditarik dan tidak dapat dibuka kembali. Dokumen pengiriman tetap tersimpan sebagai arsip read-only.</p>
        @endif
    </header>

    <section class="mt-10" aria-labelledby="vacancy-heading">
        <h2 id="vacancy-heading" class="text-xl font-semibold">Posisi yang dilamar</h2>
        <dl class="mt-5 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted">Posisi</dt>
                <dd class="mt-1 break-words font-semibold">{{ $vacancySnapshot['title'] ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Unit</dt>
                <dd class="mt-1 break-words">{{ $vacancySnapshot['unit'] ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Periode</dt>
                <dd class="mt-1 break-words">{{ $vacancySnapshot['period'] ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Kuota</dt>
                <dd class="mt-1 tabular-nums">{{ isset($vacancySnapshot['quota']) ? $vacancySnapshot['quota'] . ' peserta' : 'Tidak tersedia' }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-10 border-t border-line pt-8" aria-labelledby="profile-heading">
        <h2 id="profile-heading" class="text-xl font-semibold">Profil pelamar</h2>
        <dl class="mt-5 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted">Nama lengkap</dt>
                <dd class="mt-1 break-words font-medium">{{ $profileSnapshot['name'] ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Email</dt>
                <dd class="mt-1 break-all">{{ $profileSnapshot['email'] ?? 'Tidak tersedia' }}</dd>
            </div>
            <div>
                <dt class="text-muted">Nomor telepon</dt>
                <dd class="mt-1 break-words">{{ $profileSnapshot['phone'] ?? 'Tidak tersedia' }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-10 border-t border-line pt-8" aria-labelledby="education-heading">
        <h2 id="education-heading" class="text-xl font-semibold">Riwayat pendidikan</h2>
        @forelse ($educationSnapshot as $education)
            <div class="mt-5 border-b border-line pb-6 last:border-b-0 last:pb-0">
                <h3 class="break-words font-semibold">{{ $education['institution_name'] ?? 'Institusi tidak tersedia' }}</h3>
                <dl class="mt-4 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted">Jenjang</dt>
                        <dd class="mt-1">{{ $education['education_level'] ?? 'Tidak tersedia' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Jurusan</dt>
                        <dd class="mt-1 break-words">{{ $education['major'] ?? 'Tidak dicantumkan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Status pendidikan</dt>
                        <dd class="mt-1">{{ ! empty($education['is_active']) ? 'Masih aktif' : 'Tidak aktif' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Nilai / skala nilai</dt>
                        <dd class="mt-1 tabular-nums">{{ $education['grade'] ?? 'Tidak dicantumkan' }} / {{ $education['grade_scale'] ?? 'Tidak dicantumkan' }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <p class="mt-4 text-sm leading-6 text-muted">Tidak ada riwayat pendidikan yang dicantumkan dalam lamaran ini.</p>
        @endforelse
    </section>

    <section class="mt-10 border-t border-line pt-8" aria-labelledby="documents-heading">
        <h2 id="documents-heading" class="text-xl font-semibold">Dokumen lamaran</h2>
        <p class="mt-2 text-sm leading-6 text-muted">Daftar berkas yang tersimpan bersama pengiriman lamaran ini.</p>
        <ul class="mt-5 divide-y divide-line">
            @forelse ($documents as $document)
                <li class="py-5 first:pt-0">
                    <h3 class="font-semibold">{{ $document->label }}</h3>
                    <p class="mt-2 break-all text-sm">{{ basename($document->object_key) }}</p>
                    <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs text-muted">
                        <div>
                            <dt class="inline">Ukuran:</dt>
                            <dd class="inline tabular-nums">{{ number_format((int) $document->byte_size, 0, ',', '.') }} byte</dd>
                        </div>
                        <div>
                            <dt class="inline">Format:</dt>
                            <dd class="inline break-all">{{ $document->mime }}</dd>
                        </div>
                        <div>
                            <dt class="inline">Status pemeriksaan:</dt>
                            <dd class="inline">{{ $document->scan_status }}</dd>
                        </div>
                    </dl>
                </li>
            @empty
                <li class="text-sm leading-6 text-muted">Tidak ada dokumen pada pengiriman lamaran ini.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
