@extends('layouts.app')

@section('title', 'Panel staf')

@section('subtitle')
    {{ $isAdmin ? 'Kelola penugasan peninjau dan publikasikan keputusan lamaran.' : 'Tinjau lamaran sesuai penugasan dan kewenangan Anda.' }}
@endsection

@section('actions')
    <x-button :href="route('staff.index')" variant="primary">Buka kotak masuk lamaran</x-button>
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke dashboard</a>
    @include('staff._notices')

    <section class="mt-8" aria-labelledby="stages-heading">
        <h2 id="stages-heading" class="text-xl font-semibold text-ink">Lamaran per tahap</h2>
        <p class="mt-2 text-sm leading-6 text-muted">Jumlah berikut hanya mencakup lamaran yang dapat Anda akses.</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($stageLabels as $code => $label)
                <a href="{{ route('staff.index', ['stage' => $code]) }}" class="card flex flex-col gap-3 p-5 transition-colors hover:border-primary">
                    <span class="text-sm text-muted">{{ $label }}</span>
                    <span class="text-3xl font-semibold tabular-nums text-ink">{{ number_format($counts[$code] ?? 0, 0, ',', '.') }}</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection