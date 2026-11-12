@props(['application', 'withdrawable' => false])

@php
    $canContinue = in_array($application->stage, ['draft', 'needs_revision'], true);
    $statusLabel = \App\Http\Controllers\ApplyController::stageLabel($application->stage);
    $code = $application->stage === 'draft' ? 'Draf lamaran' : $application->public_reference;
    $note = match ($application->stage) {
        'needs_revision' => 'Ada dokumen yang perlu diperbaiki.',
        'draft' => 'Draf belum dikirim.',
        'withdrawn' => 'Ditarik; tidak dapat dibuka kembali.',
        'decided' => 'Pengumuman tersedia di detail lamaran.',
        default => 'Tidak ada tindakan yang diperlukan.',
    };
@endphp

<article class="card p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs text-muted">{{ $code }}</span>
        @if ($application->stage === 'submitted')
            <span class="tag bg-primary text-white">{{ $statusLabel }}</span>
        @elseif ($application->stage === 'needs_revision')
            <x-tag>{{ $statusLabel }}</x-tag>
        @else
            <x-tag variant="neutral">{{ $statusLabel }}</x-tag>
        @endif
    </div>
    <h3 class="mt-3 text-lg font-semibold text-ink">{{ $application->position_title }}</h3>
    <p class="mt-1 text-sm text-muted">{{ $application->unit_name }} · {{ $application->period_title }}</p>
    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-4">
        <span class="text-sm text-muted">{{ $note }}</span>
        <div class="flex flex-wrap items-center gap-2">
            @if ($withdrawable && $application->stage !== 'draft')
                <x-button
                    :href="route('applications.withdraw.confirm', $application->id)"
                    variant="danger"
                    :aria-label="'Tarik lamaran ' . $application->position_title">Tarik lamaran</x-button>
            @endif
            <x-button
                :href="$canContinue ? route('apply.create', $application->slug) : route('applications.show', $application->id)"
                :variant="$canContinue ? 'primary' : 'default'">
                {{ $canContinue ? 'Lanjutkan' : 'Lihat lamaran' }}
            </x-button>
        </div>
    </div>
    @if ($withdrawable && $application->stage === 'draft')
        <p class="mt-3 text-right text-xs text-muted">Draf dapat dihapus lewat <a href="{{ route('applications.withdraw.confirm', $application->id) }}" class="font-medium text-primary hover:underline">Tarik lamaran</a>.</p>
    @endif
</article>