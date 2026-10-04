@extends('layouts.public')

@section('title', 'Posisi magang')

@section('content')
{{-- Hero publik: permukaan Decide/Learn, bukan dashboard admin. --}}
<section aria-label="Program magang" class="border-b-8 border-primary-accent bg-primary text-white">
    <div class="mx-auto grid max-w-content gap-10 px-6 py-16 lg:grid-cols-2 lg:items-center lg:px-12 lg:py-0 lg:min-h-[486px]">
        <div class="py-6 lg:py-14">
            <p class="mb-6 flex items-center gap-2.5 text-[13px]">
                <span class="h-[7px] w-[7px] rounded-full bg-white" aria-hidden="true"></span>
                Ruang magang
            </p>
            <h1 class="max-w-xl text-[clamp(40px,4.35vw,62px)] font-semibold leading-[1.06]">
                Awali langkahmu.<br>Magang di sini.
            </h1>
            <p class="mt-6 max-w-md text-base leading-7 text-[#fff4f5]">
                Belajar langsung di balik karya perkeretaapian Indonesia. Temukan peranmu,
                bangun keahlian, dan mulai perjalanan profesionalmu.
            </p>
            <div class="mt-8 flex flex-wrap items-center gap-6">
                <a href="#posisi" class="btn border-white bg-white text-primary-hover hover:bg-[#fff1f3]">
                    Temukan peluang magang
                </a>
                <span class="text-xs leading-5 text-[#fff4f5]">Untuk mahasiswa<br>D3, D4, dan S1</span>
            </div>
        </div>

        <div class="relative hidden overflow-hidden rounded-[14px_14px_48px_14px] bg-primary-accent lg:block lg:h-[380px]" aria-hidden="true">
            {{-- Placeholder visual kereta; ganti dengan aset foto resmi saat tersedia. --}}
            <div class="absolute inset-0 bg-gradient-to-br from-[#ff2b33] via-primary-accent to-[#a60007]"></div>
            <div class="absolute bottom-6 left-6 max-w-[310px] rounded-card bg-white/95 p-4 text-ink">
                <p class="text-[13px] font-semibold">Di balik setiap perjalanan kereta</p>
                <p class="mt-1 text-[11px] text-muted">Ada tim yang merancang, membangun, dan merawatnya.</p>
            </div>
        </div>
    </div>
</section>

{{-- Katalog posisi: grid kartu dua kolom, bukan baris daftar. --}}
<div id="posisi" class="mx-auto max-w-content px-6 py-12 lg:px-12">
    <div class="mb-8">
        <h2 class="text-[29px] font-semibold">Posisi yang dibuka</h2>
        <p class="mt-2 text-sm text-muted">{{ $vacancies->count() }} posisi · lihat periode pendaftaran pada setiap posisi</p>
    </div>

    @if (count($vacancies))
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($vacancies as $v)
                <article class="card flex flex-col justify-between gap-6 p-6">
                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-soft text-primary">
                                <x-icon :name="match ($v->position->code) { 'IT-01' => 'monitor', 'ENG-02' => 'train', 'FIN-03' => 'calendar', default => 'map-pin' }" :size="24" />
                            </span>
                            <div>
                                <p class="font-semibold leading-tight">{{ $v->orgUnit->name }}</p>
                                <p class="text-xs text-muted">PT INKA (Persero) / {{ $v->position->code }}</p>
                            </div>
                        </div>
                        <h3 class="text-[22px] font-semibold leading-snug">
                            <a href="{{ route('vacancies.show', $v->slug) }}" class="hover:text-primary">{{ $v->position->title }}</a>
                        </h3>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[13px] text-muted">
                            @foreach (['Jurusan', 'Durasi', 'Jenjang pendidikan'] as $label)
                                @if ($requirement = $v->requirements->firstWhere('label', $label))
                                    <span>{{ $requirement->description }}</span>
                                @endif
                            @endforeach
                        </div>
                        <p class="mt-3 text-sm leading-6 text-ink/90">{{ $v->description }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <x-tag :variant="$v->isClosed() ? 'neutral' : 'green'">{{ $v->isClosed() ? 'Ditutup' : 'Pendaftaran dibuka' }}</x-tag>
                        <x-button :href="route('vacancies.show', $v->slug)">Lihat posisi</x-button>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card p-10 text-center">
            <h2 class="text-xl font-semibold">Belum ada posisi yang sesuai.</h2>
            <p class="mt-2 text-sm text-muted">Coba bidang lain atau lihat kembali periode berikutnya.</p>
        </div>
    @endif
</div>
@endsection
