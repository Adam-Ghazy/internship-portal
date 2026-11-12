@extends('layouts.app')

@section('title', 'Pengguna staf')

@section('subtitle')
    Kelola akun dan penugasan staf internal: buat akun, atur peran serta unit, dan nonaktifkan akses.
@endsection

@section('actions')
    <x-button :href="route('staff.users.create')" variant="primary">
        <x-icon name="users" :size="18" /> Tambah staf
    </x-button>
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('staff.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke panel staf</a>
    @include('staff._notices')

    @if ($users->isEmpty())
        <section class="card mt-8 p-6 sm:p-8" aria-labelledby="empty-heading">
            <h2 id="empty-heading" class="text-xl font-semibold">Belum ada pengguna staf</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Buat akun staf pertama untuk mulai mengelola penugasan internal.</p>
            <x-button :href="route('staff.users.create')" variant="primary" class="mt-5">
                <x-icon name="users" :size="18" /> Tambah staf
            </x-button>
        </section>
    @else
        <div class="card mt-8 max-w-full overflow-x-auto" role="region" aria-label="Daftar pengguna staf" tabindex="0">
            <table class="w-full min-w-[860px] text-left text-sm">
                <caption class="sr-only">Nama, penugasan, status, dan tindakan untuk setiap pengguna staf.</caption>
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th scope="col" class="px-5 py-4 font-semibold">Nama &amp; pengenal</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Penugasan</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Status</th>
                        <th scope="col" class="px-5 py-4 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($users as $user)
                        <tr>
                            <th scope="row" class="px-5 py-5 align-top font-normal">
                                <div class="flex items-center gap-3">
                                    <x-avatar :name="$user->name" size="md" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-ink">{{ $user->name }}</span>
                                        <span class="mt-0.5 block truncate text-ink">{{ $user->username ? '@'.$user->username : '—' }}</span>
                                        <span class="mt-0.5 block truncate text-muted">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </th>
                            <td class="px-5 py-5 align-top">
                                @forelse ($user->staffAssignments as $assignment)
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 py-1">
                                        <x-tag>{{ $roleLabels[$assignment->role_code] ?? $assignment->role_code }}</x-tag>
                                        <span class="text-ink">{{ $assignment->orgUnit?->name ?? 'Seluruh unit organisasi' }}</span>
                                        <span class="text-xs text-muted">
                                            {{ $assignment->valid_from?->format('d M Y') }} &ndash; {{ $assignment->valid_to ? $assignment->valid_to->format('d M Y') : 'sekarang' }}
                                        </span>
                                    </div>
                                @empty
                                    <span class="text-muted">Tidak ada penugasan.</span>
                                @endforelse
                            </td>
                            <td class="px-5 py-5 align-top">
                                @if ($user->disabled_at)
                                    <x-tag variant="neutral">Nonaktif</x-tag>
                                @else
                                    <x-tag variant="green">Aktif</x-tag>
                                @endif
                            </td>
                            <td class="min-w-44 px-5 py-5 align-top">
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-button :href="route('staff.users.edit', $user)">Edit</x-button>
                                    <form method="POST" action="{{ route('staff.users.toggle', $user) }}">
                                        @csrf
                                        <button type="submit" class="btn {{ $user->disabled_at === null ? 'btn-danger' : '' }}">
                                            {{ $user->disabled_at === null ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->withQueryString()->links() }}</div>
    @endif
</div>
@endsection