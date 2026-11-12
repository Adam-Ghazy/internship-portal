@extends('layouts.app')

@section('title', 'Tambah staf')

@section('subtitle')
    Buat akun dan penugasan staf baru beserta peran serta unitnya.
@endsection

@section('content')
<div class="mx-auto max-w-content px-4 pb-12 sm:px-6">
    <a href="{{ route('staff.users.index') }}" class="text-sm font-semibold text-primary hover:underline">Kembali ke pengguna staf</a>
    @include('staff._notices')

    <form method="POST" action="{{ route('staff.users.store') }}" class="card mt-6 max-w-2xl p-6 sm:p-8">
        @csrf
        @include('admin.staff._form', ['user' => null, 'assignment' => null])
    </form>
</div>
@endsection