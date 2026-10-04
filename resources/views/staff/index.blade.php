@extends('layouts.public')

@section('title', 'Kotak masuk lamaran')

@section('content')
<div class="mx-auto max-w-7xl px-6 py-12">
    <a href="{{ route('staff.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke panel staf</a>
    <header class="mt-8">
        <h1 class="text-3xl font-semibold leading-tight md:text-4xl">Kotak masuk lamaran</h1>
        <p class="mt-3 max-w-prose text-sm leading-6 text-muted">Buka detail untuk membaca salinan pengiriman dan meninjau lamaran sesuai kewenangan Anda.</p>
    </header>
    @include('staff._notices')
    <nav aria-label="Filter tahap lamaran" class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('staff.index') }}" class="btn {{ ! $stage ? 'btn-primary' : '' }}" @if (! $stage) aria-current="page" @endif>Semua tahap</a>
        @foreach ($stageLabels as $code => $label)
            <a href="{{ route('staff.index', ['stage' => $code]) }}" class="btn {{ $stage === $code ? 'btn-primary' : '' }}" @if ($stage === $code) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if ($applications->isEmpty())
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="empty-heading">
            <h2 id="empty-heading" class="text-xl font-semibold">Belum ada lamaran {{ $stage ? 'pada tahap ini' : 'yang dapat Anda akses' }}</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Lamaran akan tampil sesuai penugasan dan kewenangan Anda.</p>
            @if ($stage)<x-button :href="route('staff.index')" class="mt-5">Lihat semua tahap</x-button>@endif
        </section>
    @else
        <div class="card mt-8 max-w-full overflow-x-auto" role="region" aria-label="Daftar lamaran staf" tabindex="0">
            <table class="w-full min-w-[800px] text-left text-sm">
                <caption class="sr-only">Referensi, posisi, pelamar, tanggal pengiriman, tahap, dan tindakan lamaran.</caption>
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th scope="col" class="px-5 py-4 font-semibold">Referensi / posisi</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Pelamar</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Tanggal dikirim</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Tahap</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($applications as $application)
                        <tr>
                            <th scope="row" class="max-w-xs px-5 py-5 align-top font-normal">
                                <span class="block break-words font-semibold">{{ $application->vacancy?->position?->title ?? 'Posisi tidak tersedia' }}</span>
                                <span class="mt-1 block break-words text-muted">{{ $application->vacancy?->orgUnit?->name ?? 'Unit tidak tersedia' }}</span>
                                <span class="mt-2 block break-all text-xs text-muted">{{ $application->public_reference ?? 'Referensi tidak tersedia' }}</span>
                            </th>
                            <td class="max-w-xs break-words px-5 py-5 align-top">{{ $application->currentSubmission?->profile_snapshot['name'] ?? $application->applicant?->name ?? 'Nama tidak tersedia' }}</td>
                            <td class="px-5 py-5 align-top tabular-nums text-muted">{{ $application->currentSubmission?->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dikirim' }}</td>
                            <td class="px-5 py-5 align-top">@include('staff._stage')</td>
                            <td class="min-w-64 px-5 py-5 align-top">
                                <x-button :href="route('staff.show', $application)" :aria-label="'Tinjau detail lamaran ' . $application->public_reference">Tinjau detail</x-button>
                                @if ($isAdmin && $application->stage === 'submitted')
                                    <details class="mt-4" @if ((string) old('_application_id') === (string) $application->id) open @endif>
                                        <summary class="cursor-pointer py-2 font-semibold text-primary">Tetapkan peninjau</summary>
                                        <div class="mt-3">@include('staff._assignment')</div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $applications->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
