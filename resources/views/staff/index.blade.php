@extends('layouts.app')

@section('title', 'Kotak masuk lamaran')

@section('subtitle')
    Buka detail untuk membaca salinan pengiriman dan meninjau lamaran sesuai kewenangan Anda.
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('staff.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke panel staf</a>
    @include('staff._notices')

    <nav aria-label="Filter tahap lamaran" class="mt-6 border-b border-line">
        <div class="flex flex-wrap gap-6">
            <a href="{{ route('staff.index') }}" class="tab {{ ! $stage ? 'tab-active' : '' }}" @if (! $stage) aria-current="page" @endif>Semua tahap</a>
            @foreach ($stageLabels as $code => $label)
                <a href="{{ route('staff.index', ['stage' => $code]) }}" class="tab {{ $stage === $code ? 'tab-active' : '' }}" @if ($stage === $code) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    @if ($applications->isEmpty())
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="empty-heading">
            <h2 id="empty-heading" class="text-xl font-semibold">Belum ada lamaran {{ $stage ? 'pada tahap ini' : 'yang dapat Anda akses' }}</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Lamaran akan tampil sesuai penugasan dan kewenangan Anda.</p>
            @if ($stage)<x-button :href="route('staff.index')" class="mt-5">Lihat semua tahap</x-button>@endif
        </section>
    @else
        <div class="card mt-8 max-w-full overflow-x-auto" role="region" aria-label="Daftar lamaran staf" tabindex="0">
            <table class="w-full min-w-[840px] text-left text-sm">
                <caption class="sr-only">Pelamar, posisi, tahap, dan tindakan untuk setiap lamaran.</caption>
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th scope="col" class="px-5 py-4 font-semibold">Kandidat</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Posisi &amp; periode</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Tahap saat ini</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($applications as $application)
                        @php
                            $snapshotProfile = $application->currentSubmission?->profile_snapshot;
                            $snapshotEducations = collect($application->currentSubmission?->education_snapshot ?? []);
                            $activeEducation = $snapshotEducations->firstWhere('is_active', true) ?? $snapshotEducations->first();
                            $candidateName = data_get($snapshotProfile, 'name') ?: ($application->applicant?->name ?? 'Nama tidak tersedia');
                            $candidateSchool = $activeEducation['institution_name'] ?? null;
                            $candidateMajor = $activeEducation['major'] ?? null;
                        @endphp
                        <tr>
                            <th scope="row" class="px-5 py-5 align-top font-normal">
                                <div class="flex items-center gap-3">
                                    <x-avatar :name="$candidateName" size="md" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-ink">{{ $candidateName }}</span>
                                        @if ($candidateSchool)
                                            <span class="mt-0.5 block truncate text-muted">{{ $candidateMajor ? $candidateMajor . ' · ' : '' }}{{ $candidateSchool }}</span>
                                        @endif
                                    </span>
                                </div>
                            </th>
                            <td class="max-w-xs px-5 py-5 align-top">
                                <span class="block break-words font-semibold text-ink">{{ $application->vacancy?->position?->title ?? 'Posisi tidak tersedia' }}</span>
                                <span class="mt-1 block break-words text-muted">{{ $application->vacancy?->orgUnit?->name ?? 'Unit tidak tersedia' }}</span>
                                @if ($application->public_reference)
                                    <span class="mt-2 block break-all text-xs text-muted">{{ $application->public_reference }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-5 align-top">@include('staff._stage')</td>
                            <td class="min-w-56 px-5 py-5 align-top">
                                <x-button :href="route('staff.show', $application)" :aria-label="'Tinjau detail lamaran ' . $application->public_reference">Tinjau</x-button>
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