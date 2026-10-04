@extends('layouts.public')

@section('title', 'Lamaran saya')

@section('content')
<div class="mx-auto max-w-content px-6 py-12 lg:px-12">
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

    <div class="mt-8 flex flex-wrap items-start justify-between gap-5">
        <div>
            <h1 class="text-3xl font-semibold leading-tight md:text-4xl">Lamaran saya</h1>
            <p class="mt-3 text-sm leading-6 text-muted">Lanjutkan draf atau lihat data lamaran yang sudah dikirim.</p>
        </div>
        <x-button :href="route('home') . '#posisi'">Lihat posisi magang</x-button>
    </div>

    @if ($applications->isEmpty())
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="empty-heading">
            <h2 id="empty-heading" class="text-xl font-semibold">Anda belum memiliki lamaran</h2>
            <p class="mt-3 max-w-prose text-sm leading-6 text-muted">Pilih posisi magang pada katalog, baca persyaratannya, lalu mulai melengkapi lamaran.</p>
            <x-button :href="route('home') . '#posisi'" variant="primary" class="mt-6">Pilih posisi magang</x-button>
        </section>
    @else
        <div class="card mt-8 overflow-x-auto" role="region" aria-label="Daftar lamaran saya" tabindex="0">
            <table class="w-full min-w-[640px] text-left text-sm">
                <caption class="sr-only">Posisi, status, tanggal kirim, dan tindakan untuk setiap lamaran.</caption>
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Posisi</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Tanggal dikirim</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($applications as $application)
                        @php
                            $canContinue = in_array($application->stage, ['draft', 'needs_revision'], true);
                            $submittedDate = null;
                            if ($application->stage !== 'draft' && ! empty($application->submitted_at)) {
                                try {
                                    $submittedDate = \Illuminate\Support\Carbon::parse($application->submitted_at)->locale('id')->translatedFormat('d M Y, H:i');
                                } catch (\Throwable $exception) {
                                    $submittedDate = null;
                                }
                            }
                        @endphp
                        <tr>
                            <th scope="row" class="max-w-sm px-6 py-5 font-normal">
                                <span class="block break-words font-semibold">{{ $application->position_title }}</span>
                                <span class="mt-1 block break-words text-muted">{{ $application->unit_name }}</span>
                                @if ($application->stage !== 'draft')
                                    <span class="mt-2 block break-all text-xs text-muted">Referensi: {{ $application->public_reference }}</span>
                                @endif
                            </th>
                            <td class="px-6 py-5 align-top">
                                @if ($application->stage === 'submitted')
                                    <span class="tag bg-primary text-white">{{ $application->stage }}</span>
                                @elseif ($application->stage === 'needs_revision')
                                    <x-tag>{{ $application->stage }}</x-tag>
                                @else
                                    <x-tag variant="neutral">{{ $application->stage }}</x-tag>
                                @endif
                                @if ($application->stage === 'draft')
                                    <p class="mt-2 text-xs text-muted">Belum dikirim</p>
                                @elseif ($application->stage === 'needs_revision')
                                    <p class="mt-2 text-xs text-primary-hover">Perlu diperbaiki</p>
                                @elseif ($application->stage === 'withdrawn')
                                    <p class="mt-2 text-xs text-muted">Ditarik; tidak dapat dibuka kembali</p>
                                @endif
                            </td>
                            <td class="px-6 py-5 align-top tabular-nums text-muted">{{ $submittedDate ?? ($application->stage === 'draft' ? 'Belum dikirim' : 'Tidak tersedia') }}</td>
                            <td class="px-6 py-5 align-top">
                                <x-button :href="$canContinue ? route('apply.create', $application->slug) : route('applications.show', $application->id)" :aria-label="($canContinue ? 'Lanjutkan lamaran ' : 'Lihat lamaran ') . $application->position_title">{{ $canContinue ? 'Lanjutkan' : 'Lihat' }}</x-button>
                                @if (in_array($application->stage, $withdrawableStages, true))
                                    <x-button :href="route('applications.withdraw.confirm', $application->id)" variant="danger" class="mt-2" :aria-label="'Tarik lamaran ' . $application->position_title">Tarik lamaran</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
