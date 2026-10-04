<header class="border-b border-line bg-paper">
    <div class="mx-auto flex max-w-content flex-wrap items-center justify-between gap-4 px-6 py-5 lg:h-[94px] lg:flex-nowrap lg:px-12 lg:py-0">
        <a href="{{ url('/') }}" class="flex items-center gap-5 text-ink no-underline">
            <span class="text-[29px] font-bold leading-none text-primary">Ruang Magang</span>
            <span class="hidden border-l border-line pl-5 leading-tight sm:block">
                <span class="block text-[17px] font-semibold">Portal Rekrutmen Magang</span>
                <span class="mt-1 block text-xs text-muted">Rekonstruksi portofolio · data sintetis</span>
            </span>
        </a>

        <nav aria-label="Navigasi utama" class="flex flex-wrap items-center gap-4 whitespace-nowrap sm:gap-7">
            <a href="{{ url('/') }}#posisi" class="text-sm font-medium hover:text-primary">Posisi magang</a>
            <a href="#" class="text-sm font-medium hover:text-primary">Alur seleksi</a>
            @auth('web')
                <x-button :href="route('dashboard')">Akun saya</x-button>
            @else
                <x-button :href="route('login')">Masuk</x-button>
            @endauth
        </nav>
    </div>
</header>
