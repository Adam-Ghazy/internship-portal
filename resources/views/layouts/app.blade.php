@php
    $authUser = auth('web')->user();
    $isStaff = $authUser?->isStaff() ?? false;
    $roleLabel = $isStaff
        ? ($authUser->hasRole('admin') ? 'Admin / HR' : ($authUser->hasRole('sm') ? 'Senior Manager' : 'Manager'))
        : 'Kandidat';
    $navGroups = [];
    if ($authUser && $isStaff) {
        $queueLabel = $authUser->hasRole('admin') ? 'Antrean lamaran' : 'Tugas penilaian';
        $staffItems = [
            ['route' => 'staff.dashboard', 'label' => 'Panel staf', 'icon' => 'monitor', 'active' => request()->routeIs('staff.dashboard')],
            ['route' => 'staff.index', 'label' => $queueLabel, 'icon' => 'inbox', 'active' => request()->routeIs('staff.index', 'staff.show')],
        ];
        if ($authUser->hasRole('admin')) {
            $staffItems[] = ['route' => 'staff.users.index', 'label' => 'Pengguna staf', 'icon' => 'users', 'active' => request()->routeIs('staff.users.*')];
        }
        $navGroups[] = ['label' => 'Ruang kerja', 'items' => $staffItems];
    } elseif ($authUser) {
        $navGroups[] = [
            'label' => 'Ruang kerja',
            'items' => [
                ['route' => 'applications.index', 'label' => 'Lamaran saya', 'icon' => 'file-text', 'active' => request()->routeIs('applications.*', 'apply.create')],
                ['route' => 'home', 'label' => 'Cari lowongan', 'icon' => 'monitor', 'active' => request()->routeIs('home', 'vacancies.show')],
            ],
        ];
    }
    $initial = $authUser?->name ? strtoupper(substr(trim($authUser->name), 0, 1)) : '?';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Magang') · {{ config('app.name', 'Ruang Magang') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-paper focus:px-4 focus:py-3">
        Lewati ke konten utama
    </a>

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-line bg-paper md:flex">
            <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-line px-5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary text-[13px] font-bold leading-none text-white" aria-hidden="true">IN</span>
                <span class="truncate text-[15px] font-bold text-ink">Ruang Magang</span>
            </div>

            <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-3 py-6">
                @foreach ($navGroups as $group)
                    <p class="sidebar-label">{{ $group['label'] }}</p>
                    <div class="mt-1 space-y-1">
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route']) }}"
                               @class(['sidebar-link', 'sidebar-link-active' => $item['active']])
                               @if ($item['active']) aria-current="page" @endif>
                                <x-icon :name="$item['icon']" :size="20" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="flex shrink-0 items-center gap-3 border-t border-line p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary-hover" aria-hidden="true">{{ $initial }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold leading-tight text-ink">{{ $authUser?->name }}</span>
                    <span class="block truncate text-xs leading-tight text-muted">{{ $roleLabel }}</span>
                </span>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top bar: akun + keluar (merek pindah ke sidebar; tampil di bar ini hanya pada layar kecil) --}}
            <header class="sticky top-0 z-10 border-b border-line bg-paper">
                <div class="flex h-14 items-center gap-4 px-4 sm:px-6">
                    <a href="{{ route($isStaff ? 'staff.index' : 'applications.index') }}" class="flex min-w-0 items-center gap-2.5 no-underline md:hidden">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary text-[13px] font-bold leading-none text-white" aria-hidden="true">IN</span>
                        <span class="truncate text-[15px] font-bold text-ink">Ruang Magang</span>
                    </a>

                    <div class="ml-auto flex items-center gap-4">
                        <span class="hidden text-sm text-muted sm:inline">{{ $authUser?->email }}</span>
                        <span class="hidden h-5 w-px bg-line sm:block" aria-hidden="true"></span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-primary hover:text-primary-hover">Keluar</button>
                        </form>
                    </div>
                </div>
            </header>

            <main id="main" tabindex="-1" class="flex-1 bg-surface">
                {{-- Header halaman (sapaan + judul + aksi) --}}
                <div class="mx-auto max-w-content px-4 pb-6 pt-8 sm:px-6">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-muted">Halo, {{ $authUser?->name }} · {{ $roleLabel }}</p>
                            <h1 class="mt-1 text-2xl font-semibold leading-tight text-ink md:text-3xl">@yield('title', 'Ruang Magang')</h1>
                            @hasSection('subtitle')
                                <p class="mt-2 max-w-prose text-sm leading-6 text-muted">@yield('subtitle')</p>
                            @endif
                        </div>
                        @hasSection('actions')
                            <div class="flex flex-wrap items-center gap-3">@yield('actions')</div>
                        @endif
                    </div>
                </div>

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>