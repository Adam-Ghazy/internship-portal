@extends('layouts.app')

@section('title', 'Lamaran saya')

@section('subtitle')
    Lanjutkan draf atau lihat data lamaran yang sudah dikirim.
@endsection

@section('actions')
    <x-button :href="route('home') . '#posisi'">Lihat posisi magang</x-button>
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke dashboard</a>

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

    @if ($applications->isEmpty())
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="empty-heading">
            <h2 id="empty-heading" class="text-xl font-semibold">Anda belum memiliki lamaran</h2>
            <p class="mt-3 max-w-prose text-sm leading-6 text-muted">Pilih posisi magang pada katalog, baca persyaratannya, lalu mulai melengkapi lamaran.</p>
            <x-button :href="route('home') . '#posisi'" variant="primary" class="mt-6">Pilih posisi magang</x-button>
        </section>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($applications as $application)
                @include('applications._card', [
                    'application' => $application,
                    'withdrawable' => in_array($application->stage, $withdrawableStages, true),
                ])
            @endforeach
        </div>
    @endif
</div>
@endsection