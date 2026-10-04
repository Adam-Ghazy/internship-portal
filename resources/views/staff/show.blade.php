@extends('layouts.public')

@section('title', 'Tinjau lamaran')

@section('content')
@php
    $profileSnapshot = $submission?->profile_snapshot ?? [];
    $educationSnapshot = $submission?->education_snapshot ?? [];
    $vacancySnapshot = $submission?->vacancy_snapshot ?? [];
    $outcomeLabels = ['recommended' => 'Direkomendasikan', 'not_recommended' => 'Tidak direkomendasikan', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'];
    $reviewStageLabels = ['manager' => 'Manajer', 'sm' => 'Manajer senior'];
    $reviewStatusLabels = ['blocked' => 'Menunggu tinjauan manajer', 'pending' => 'Menunggu tinjauan', 'completed' => 'Selesai', 'superseded' => 'Digantikan', 'cancelled' => 'Dibatalkan'];
@endphp
<div class="mx-auto max-w-5xl px-6 py-12">
    <a href="{{ route('staff.index') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke kotak masuk lamaran</a>
    <header class="mt-8">
        <h1 class="text-3xl font-semibold leading-tight md:text-4xl">Tinjau lamaran</h1>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            @include('staff._stage')
            <p class="break-all text-sm">Referensi: <strong>{{ $application->public_reference ?? 'Tidak tersedia' }}</strong></p>
        </div>
        <p class="mt-4 max-w-prose text-sm leading-6 text-muted">Data berikut merupakan salinan pengiriman terkini, bukan profil pelamar yang dapat berubah. Catatan tinjauan bersifat internal.</p>
    </header>
    @include('staff._notices')

    @if ($isAdmin && $application->stage === 'submitted')
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="assignment-heading">
            <h2 id="assignment-heading" class="text-xl font-semibold">Tetapkan peninjau</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Pilih manajer dan manajer senior dari unit posisi yang dilamar. Lamaran akan diteruskan ke tinjauan manajer.</p>
            <div class="mt-5 max-w-xl">@include('staff._assignment')</div>
        </section>
    @endif

    @if ($submission)
        <dl class="mt-8 grid gap-4 border-y border-line py-5 text-sm sm:grid-cols-2">
            <div><dt class="text-muted">Tanggal dikirim</dt><dd class="mt-1 font-medium tabular-nums">{{ $submission->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Tidak tersedia' }}</dd></div>
            <div><dt class="text-muted">Versi pengiriman</dt><dd class="mt-1 font-medium tabular-nums">{{ $submission->version_no }}</dd></div>
        </dl>
        <section class="mt-8" aria-labelledby="vacancy-heading">
            <h2 id="vacancy-heading" class="text-xl font-semibold">Posisi yang dilamar</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                @foreach (['title' => 'Posisi', 'unit' => 'Unit', 'period' => 'Periode', 'quota' => 'Kuota'] as $key => $label)
                    <div><dt class="text-muted">{{ $label }}</dt><dd class="mt-1 break-words font-medium">{{ $vacancySnapshot[$key] ?? 'Tidak tersedia' }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="mt-10 border-t border-line pt-8" aria-labelledby="profile-heading">
            <h2 id="profile-heading" class="text-xl font-semibold">Profil pelamar</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                @foreach (['name' => 'Nama lengkap', 'email' => 'Email', 'phone' => 'Nomor telepon'] as $key => $label)
                    <div><dt class="text-muted">{{ $label }}</dt><dd class="mt-1 break-words font-medium">{{ $profileSnapshot[$key] ?? 'Tidak dicantumkan' }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="mt-10 border-t border-line pt-8" aria-labelledby="education-heading">
            <h2 id="education-heading" class="text-xl font-semibold">Riwayat pendidikan</h2>
            @forelse ($educationSnapshot as $education)
                <div class="mt-5 border-b border-line pb-6 last:border-b-0">
                    <h3 class="break-words font-semibold">{{ $education['institution_name'] ?? 'Institusi tidak tersedia' }}</h3>
                    <dl class="mt-4 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted">Jenjang</dt><dd class="mt-1">{{ $education['education_level'] ?? 'Tidak tersedia' }}</dd></div>
                        <div><dt class="text-muted">Jurusan</dt><dd class="mt-1 break-words">{{ $education['major'] ?? 'Tidak dicantumkan' }}</dd></div>
                        <div><dt class="text-muted">Status pendidikan</dt><dd class="mt-1">{{ ! empty($education['is_active']) ? 'Masih aktif' : 'Tidak aktif' }}</dd></div>
                        <div><dt class="text-muted">Nilai / skala nilai</dt><dd class="mt-1 tabular-nums">{{ $education['grade'] ?? 'Tidak dicantumkan' }} / {{ $education['grade_scale'] ?? 'Tidak dicantumkan' }}</dd></div>
                    </dl>
                </div>
            @empty
                <p class="mt-4 text-sm leading-6 text-muted">Tidak ada riwayat pendidikan pada pengiriman ini.</p>
            @endforelse
        </section>
        <section class="mt-10 border-t border-line pt-8" aria-labelledby="documents-heading">
            <h2 id="documents-heading" class="text-xl font-semibold">Dokumen pengiriman</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Unduhan dilindungi dan hanya tersedia sesuai kewenangan Anda.</p>
            <ul class="mt-5 divide-y divide-line">
                @forelse ($documents as $document)
                    <li class="flex flex-wrap items-start justify-between gap-4 py-5">
                        <div class="min-w-0 flex-1">
                            <h3 class="break-words font-semibold">{{ $document->label }}</h3>
                            <p class="mt-2 break-all text-sm">{{ basename($document->object_key) }}</p>
                            <p class="mt-2 break-words text-xs leading-5 text-muted">{{ number_format((int) $document->byte_size, 0, ',', '.') }} byte · {{ $document->mime }}</p>
                            <p class="mt-1 text-xs text-muted">Pemeriksaan: {{ match ($document->scan_status) { 'clean' => 'Lolos', 'pending' => 'Menunggu', 'infected' => 'Terdeteksi ancaman', 'failed' => 'Gagal', default => $document->scan_status } }}</p>
                        </div>
                        <x-button :href="route('staff.documents', [$application, $document->id])" :aria-label="'Unduh ' . $document->label">Unduh dokumen</x-button>
                    </li>
                @empty
                    <li class="text-sm leading-6 text-muted">Tidak ada dokumen pada pengiriman ini.</li>
                @endforelse
            </ul>
        </section>
    @else
        <section class="card mt-8 p-6" aria-labelledby="submission-heading">
            <h2 id="submission-heading" class="text-xl font-semibold">Pengiriman belum tersedia</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Lamaran ini belum memiliki salinan pengiriman yang dapat ditinjau.</p>
        </section>
    @endif

    @if ($pendingReview)
        <section class="card mt-10 p-6 sm:p-8" aria-labelledby="review-heading">
            <h2 id="review-heading" class="text-xl font-semibold">Tinjauan {{ $reviewStageLabels[$pendingReview->stage] ?? 'staf' }}</h2>
            <p id="review-help" class="mt-3 max-w-prose text-sm leading-6 text-muted">{{ $pendingReview->stage === 'manager' ? 'Rekomendasi manajer akan diteruskan ke manajer senior. Tidak direkomendasikan bukan penolakan akhir; lamaran tetap masuk ke tinjauan manajer senior.' : 'Tetapkan keputusan berdasarkan salinan pengiriman dan riwayat tinjauan. Admin akan memublikasikan hasil kepada pelamar.' }}</p>
            <form action="{{ route('staff.review', $application) }}" method="POST" class="mt-6 max-w-xl space-y-5">
                @csrf
                <div>
                    <label for="outcome" class="mb-2 block text-sm font-medium">Hasil tinjauan (wajib)</label>
                    <select id="outcome" name="outcome" class="form-input w-full" required aria-describedby="review-help{{ $errors->has('outcome') ? ' outcome-error' : '' }}" @error('outcome') aria-invalid="true" @enderror>
                        <option value="">Pilih hasil tinjauan</option>
                        @foreach (($pendingReview->stage === 'manager' ? ['recommended', 'not_recommended'] : ['accepted', 'rejected']) as $outcome)
                            <option value="{{ $outcome }}" @selected(old('outcome') === $outcome)>{{ $outcomeLabels[$outcome] }}</option>
                        @endforeach
                    </select>
                    @error('outcome')<p id="outcome-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="note_internal" class="mb-2 block text-sm font-medium">Catatan internal (wajib)</label>
                    <textarea id="note_internal" name="note_internal" rows="5" maxlength="5000" required class="form-input w-full" aria-describedby="note-help{{ $errors->has('note_internal') ? ' note-error' : '' }}" @error('note_internal') aria-invalid="true" @enderror>{{ old('note_internal') }}</textarea>
                    <p id="note-help" class="mt-2 text-xs leading-5 text-muted">Jelaskan alasan untuk setiap hasil tinjauan. Maksimal 5.000 karakter; tidak ditampilkan kepada pelamar.</p>
                    @error('note_internal')<p id="note-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" variant="primary">Simpan hasil tinjauan</x-button>
            </form>
        </section>
    @endif

    <section class="mt-10 border-t border-line pt-8" aria-labelledby="history-heading">
        <h2 id="history-heading" class="text-xl font-semibold">Riwayat tinjauan</h2>
        <p class="mt-2 text-sm text-muted">Catatan peninjauan yang tersimpan untuk lamaran ini.</p>
        <ol class="mt-5 divide-y divide-line">
            @forelse ($reviews as $review)
                <li class="py-5 first:pt-0">
                    <h3 class="font-semibold">{{ $reviewStageLabels[$review->stage] ?? $review->stage }} · {{ $review->reviewer_name ?? 'Peninjau tidak tersedia' }}</h3>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-tag variant="neutral">{{ $review->outcome ? ($outcomeLabels[$review->outcome] ?? $review->outcome) : ($reviewStatusLabels[$review->status] ?? $review->status) }}</x-tag>
                        @if ($review->acted_at)<span class="text-xs tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($review->acted_at)->locale('id')->translatedFormat('d M Y, H:i') }}</span>@endif
                    </div>
                    @if ($review->note_internal)<p class="mt-3 whitespace-pre-line break-words text-sm leading-6">{{ $review->note_internal }}</p>@endif
                </li>
            @empty
                <li class="text-sm leading-6 text-muted">Belum ada tinjauan pada pengiriman ini.</li>
            @endforelse
        </ol>
    </section>

    @if ($publication)
        <section class="card mt-10 p-6 sm:p-8" aria-labelledby="publication-heading">
            <h2 id="publication-heading" class="text-xl font-semibold">Keputusan telah dipublikasikan</h2>
            <p class="mt-2 text-sm tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($publication->published_at)->locale('id')->translatedFormat('d M Y, H:i') }}</p>
            <p class="mt-4 whitespace-pre-line break-words text-sm leading-6">{{ $publication->public_message }}</p>
        </section>
    @elseif ($isAdmin && $application->stage === 'decided')
        <section class="card mt-10 p-6 sm:p-8" aria-labelledby="publication-heading">
            <h2 id="publication-heading" class="text-xl font-semibold">Publikasikan keputusan</h2>
            <p id="publication-help" class="mt-3 max-w-prose text-sm leading-6 text-muted">Pesan ini akan ditampilkan kepada pelamar bersama keputusan akhir. Periksa riwayat tinjauan sebelum memublikasikan. Minimal 10 dan maksimal 5.000 karakter.</p>
            <form action="{{ route('staff.publish', $application) }}" method="POST" class="mt-6 max-w-xl space-y-5">
                @csrf
                <div>
                    <label for="public_message" class="mb-2 block text-sm font-medium">Pesan untuk pelamar (wajib)</label>
                    <textarea id="public_message" name="public_message" rows="5" required minlength="10" maxlength="5000" class="form-input w-full" aria-describedby="publication-help{{ $errors->has('public_message') ? ' publication-error' : '' }}" @error('public_message') aria-invalid="true" @enderror>{{ old('public_message') }}</textarea>
                    @error('public_message')<p id="publication-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" variant="primary">Publikasikan kepada pelamar</x-button>
            </form>
        </section>
    @endif

    <section class="mt-10 border-t border-line pt-8" aria-labelledby="events-heading">
        <h2 id="events-heading" class="text-xl font-semibold">Log aktivitas</h2>
        <ol class="mt-5 divide-y divide-line">
            @forelse ($events as $event)
                <li class="flex flex-wrap justify-between gap-3 py-4 text-sm">
                    <div class="min-w-0">
                        <p class="break-words font-medium">{{ match ($event->event_type) { 'application.submitted' => 'Lamaran dikirim', 'review.assigned' => 'Peninjau ditetapkan', 'review.completed' => 'Tinjauan disimpan', 'decision.published' => 'Keputusan dipublikasikan', 'application.withdrawn' => 'Lamaran ditarik', default => $event->event_type } }}</p>
                        <p class="mt-1 break-words text-muted">{{ $event->actor_name ?? 'Sistem' }}</p>
                    </div>
                    <span class="text-xs tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($event->occurred_at)->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                </li>
            @empty
                <li class="text-sm leading-6 text-muted">Belum ada aktivitas yang tercatat.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
