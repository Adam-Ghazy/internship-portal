@extends('layouts.app')

@section('title', 'Edit staf')

@section('subtitle')
    Ubah data akun, peran, unit organisasi, dan masa aktif penugasan.
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('staff.users.index') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke pengguna staf</a>
    @include('staff._notices')

    <form method="POST" action="{{ route('staff.users.update', $user) }}" class="card mt-6 max-w-2xl p-6 sm:p-8">
        @csrf
        @method('PATCH')
        @include('admin.staff._form', ['user' => $user, 'assignment' => $assignment])
    </form>
</div>
@endsection