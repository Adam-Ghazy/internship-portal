@extends('layouts.public')

@section('title', 'Tarik lamaran')

@section('content')
<div class="mx-auto max-w-2xl px-6 py-12">
    <a href="{{ route('applications.index') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke lamaran saya</a>

    <h1 class="mt-8 text-3xl font-semibold leading-tight md:text-4xl">Tarik lamaran</h1>
    <dl class="mt-6 space-y-4 border-y border-line py-5 text-sm">
        <div>
            <dt class="text-muted">Posisi</dt>
            <dd class="mt-1 break-words font-semibold">{{ $vacancy->position->title }}</dd>
        </div>
        <div>
            <dt class="text-muted">Unit</dt>
            <dd class="mt-1 break-words">{{ $vacancy->orgUnit->name }}</dd>
        </div>
        <div>
            <dt class="text-muted">Status saat ini</dt>
            <dd class="mt-1">{{ $application->stage }}</dd>
        </div>
    </dl>

    @if ($application->stage === 'draft')
        <p id="withdrawal-warning" class="mt-6 text-base leading-7">Draf lamaran yang belum pernah dikirim akan dihapus beserta data draf dan daftar dokumennya. Anda dapat membuat lamaran baru untuk posisi ini selama pendaftaran masih dibuka.</p>
    @else
        <p id="withdrawal-warning" class="mt-6 text-base font-semibold leading-7">Lamaran akan ditarik dan tidak bisa dikirim ulang untuk posisi ini.</p>
        <p class="mt-3 text-sm leading-6 text-muted">Data dan dokumen yang sudah dikirim tetap tersimpan dan hanya dapat dilihat.</p>
    @endif

    <form method="POST" action="{{ route('applications.withdraw', $application->id) }}" aria-describedby="withdrawal-warning" class="mt-8 flex flex-wrap gap-3">
        @csrf
        <x-button :href="route('applications.index')">Batal</x-button>
        <x-button type="submit" variant="danger">{{ $application->stage === 'draft' ? 'Hapus draf lamaran' : 'Tarik lamaran' }}</x-button>
    </form>
</div>
@endsection
