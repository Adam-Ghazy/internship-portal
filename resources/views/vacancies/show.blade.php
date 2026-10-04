@extends('layouts.public')

@section('title', $vacancy->position->title)

@section('content')
<div class="mx-auto max-w-content px-6 py-12 lg:px-12">
    <a href="{{ route('home') }}#posisi" class="text-sm font-semibold text-primary hover:underline">Kembali ke katalog posisi</a>

    <div class="mt-8 grid gap-10 lg:grid-cols-3">
        <article class="min-w-0 lg:col-span-2">
            <h1 class="text-3xl font-semibold leading-tight md:text-4xl">{{ $vacancy->position->title }}</h1>
            <p class="mt-3 text-muted">{{ $vacancy->orgUnit->name }} · PT INKA (Persero) / {{ $vacancy->position->code }}</p>

            <section class="mt-10" aria-labelledby="description-heading">
                <h2 id="description-heading" class="text-xl font-semibold">Tentang posisi</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-7">{{ $vacancy->description }}</p>
            </section>

            <section class="mt-10" aria-labelledby="requirements-heading">
                <h2 id="requirements-heading" class="text-xl font-semibold">Persyaratan</h2>
                <ul class="mt-4 space-y-4">
                    @forelse ($vacancy->requirements as $requirement)
                        <li>
                            <h3 class="font-semibold">{{ $requirement->label }}</h3>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-muted">{{ $requirement->description }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-muted">Belum ada persyaratan tambahan.</li>
                    @endforelse
                </ul>
            </section>

            <section class="mt-10" aria-labelledby="documents-heading">
                <h2 id="documents-heading" class="text-xl font-semibold">Dokumen wajib</h2>
                <ul class="mt-4 list-inside list-disc space-y-2 text-sm leading-6">
                    @forelse ($vacancy->documentRequirements->where('required', true) as $requirement)
                        <li>{{ $requirement->documentType->label }}</li>
                    @empty
                        <li>Tidak ada dokumen wajib untuk posisi ini.</li>
                    @endforelse
                </ul>
            </section>
        </article>

        <aside aria-label="Informasi pendaftaran" class="card self-start p-6">
            <x-tag :variant="$vacancy->isClosed() ? 'neutral' : 'green'">{{ $vacancy->isClosed() ? 'Ditutup' : 'Pendaftaran dibuka' }}</x-tag>
            <dl class="mt-6 space-y-5 text-sm">
                <div>
                    <dt class="text-muted">Program</dt>
                    <dd class="mt-1 font-semibold">{{ $vacancy->program->name }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Periode</dt>
                    <dd class="mt-1 font-semibold">{{ $vacancy->period->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Pendaftaran dibuka</dt>
                    <dd class="mt-1">{{ $vacancy->period->opens_at->format('d M Y, H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Pendaftaran ditutup</dt>
                    <dd class="mt-1">{{ $vacancy->period->closes_at->format('d M Y, H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Pelaksanaan magang</dt>
                    <dd class="mt-1">{{ $vacancy->starts_on->format('d M Y') }} – {{ $vacancy->ends_on->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Kuota</dt>
                    <dd class="mt-1 font-semibold">{{ $vacancy->quota }} peserta</dd>
                </div>
            </dl>
            @if (! $vacancy->isClosed())
                <x-button :href="route('apply.create', $vacancy->slug)" variant="primary" full class="mt-6">Lamar posisi ini</x-button>
            @else
                <p class="mt-6 text-sm leading-6 text-muted">Periode pendaftaran sudah berakhir. Silakan lihat posisi lain di katalog.</p>
            @endif
        </aside>
    </div>
</div>
@endsection
