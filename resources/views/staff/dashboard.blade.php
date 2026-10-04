@extends('layouts.public')

@section('title', 'Panel staf')

@section('content')
<div class="mx-auto max-w-5xl px-6 py-12">
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke dashboard</a>
    <header class="mt-8">
        <h1 class="text-3xl font-semibold leading-tight md:text-4xl">Panel staf</h1>
        <p class="mt-3 max-w-prose text-sm leading-6 text-muted">{{ $isAdmin ? 'Kelola penugasan peninjau dan publikasikan keputusan lamaran.' : 'Tinjau lamaran sesuai penugasan dan kewenangan Anda.' }}</p>
    </header>
    @include('staff._notices')
    <section class="mt-8 card p-6 sm:p-8" aria-labelledby="stages-heading">
        <h2 id="stages-heading" class="text-xl font-semibold">Lamaran per tahap</h2>
        <p class="mt-2 text-sm leading-6 text-muted">Jumlah berikut hanya mencakup lamaran yang dapat Anda akses.</p>
        <dl class="mt-6 divide-y divide-line">
            @foreach ($stageLabels as $code => $label)
                <div class="flex items-center justify-between gap-4 py-4">
                    <dt><a href="{{ route('staff.index', ['stage' => $code]) }}" class="font-medium text-primary hover:underline">{{ $label }}</a></dt>
                    <dd class="text-xl font-semibold tabular-nums">{{ number_format($counts[$code] ?? 0, 0, ',', '.') }}</dd>
                </div>
            @endforeach
        </dl>
        <x-button :href="route('staff.index')" variant="primary" class="mt-6">Buka kotak masuk lamaran</x-button>
    </section>
</div>
@endsection
