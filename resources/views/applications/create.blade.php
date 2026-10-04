@extends('layouts.public')

@section('title', 'Lamar '.$vacancy->position->title)

@section('content')
@php
    $recoveredProfile = (string) old('profile_form', '') === '1';
    $educationRows = $recoveredProfile ? old('educations', []) : $educations;
    $requiredCount = $vacancy->documentRequirements->where('required', true)->count();
    $readyToSubmit = $profileComplete && $missingDocuments->isEmpty();
@endphp
<div class="mx-auto max-w-content px-4 py-10 sm:px-6 lg:px-12">
    <a href="{{ route('applications.index') }}" class="text-sm font-semibold text-primary underline underline-offset-4 hover:text-primary-hover">Kembali ke lamaran saya</a>
    <header class="mt-7 max-w-3xl">
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">Lamar {{ $vacancy->position->title }}</h1>
        <p class="mt-3 text-sm leading-6 text-muted">{{ $vacancy->orgUnit->name }} · {{ $vacancy->period->title }}</p>
        <p class="mt-5 text-sm leading-7">Lamaran ini masih berupa draf. Simpan profil dan pendidikan, unggah dokumen, lalu kirim lamaran. Menyimpan draf belum mengirimkan lamaran kepada tim rekrutmen.</p>
    </header>

    <nav aria-label="Kelengkapan draf lamaran" class="mt-8 border-y border-line py-5">
        <ol class="grid gap-5 text-sm sm:grid-cols-3">
            <li><a href="#profile-heading" class="font-semibold underline underline-offset-4 hover:text-primary">Profil dan pendidikan</a><p class="mt-1 text-muted">{{ $profileComplete ? 'Profil sudah tersimpan' : 'Profil belum lengkap' }} · Pendidikan opsional</p></li>
            <li><a href="#documents-heading" class="font-semibold underline underline-offset-4 hover:text-primary">Dokumen pendukung</a><p class="mt-1 text-muted">{{ $requiredCount - $missingDocuments->count() }} dari {{ $requiredCount }} dokumen wajib tersedia</p></li>
            <li><a href="#submit-heading" class="font-semibold underline underline-offset-4 hover:text-primary">Kirim lamaran</a><p class="mt-1 text-muted">{{ $readyToSubmit ? 'Draf siap diperiksa dan dikirim' : 'Lengkapi data wajib terlebih dahulu' }}</p></li>
        </ol>
    </nav>

    @if (session('status'))
        <div role="status" class="mt-6 rounded-card border border-line bg-paper p-4 text-sm leading-6">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-card bg-primary-soft p-5 text-sm leading-6">
            <h2 class="font-semibold text-primary-hover">Tindakan terakhir belum berhasil.</h2>
            <p class="mt-1">Periksa pesan di bawah dan kolom yang ditandai, lalu ulangi tindakan Anda. Berkas yang gagal diunggah perlu dipilih kembali.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-primary-hover">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @error('lock_version')
                <p id="lock-version-error" class="mt-3 font-medium">Draf berubah di sesi lain. Salin perubahan yang belum disimpan, <a href="{{ route('apply.create', $vacancy->slug) }}" class="underline underline-offset-4">muat ulang draf terbaru</a>, lalu masukkan dan simpan kembali perubahan Anda.</p>
            @enderror
            @error('application')
                <p id="application-error" class="mt-3">Periksa <a href="{{ route('applications.show', $application->id) }}" class="font-semibold underline underline-offset-4">status lamaran Anda</a> sebelum melanjutkan.</p>
            @enderror
        </div>
    @endif

    <p id="profile-dirty-notice" role="status" class="mt-6 rounded-card bg-primary-soft p-4 text-sm leading-6 text-primary-hover" @if (! $recoveredProfile) hidden @endif>Simpan profil dan pendidikan terlebih dahulu. Unggah dokumen dan kirim lamaran dinonaktifkan sementara agar perubahan Anda tidak hilang.</p>

    <div class="mt-8 grid items-start gap-8 lg:grid-cols-3">
        <form id="profile-form" action="{{ route('apply.profile', $application->id) }}" method="POST" class="card min-w-0 p-5 sm:p-8 lg:col-span-2" data-recovered="{{ $recoveredProfile ? 'true' : 'false' }}">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $draft->lock_version }}">
            <input type="hidden" name="profile_form" value="1">
            <section aria-labelledby="profile-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="profile-heading" class="text-xl font-semibold">Profil pelamar</h2>
                    <x-tag :variant="$profileComplete ? 'green' : 'neutral'">{{ $profileComplete ? 'Tersimpan' : 'Belum lengkap' }}</x-tag>
                </div>
                <p class="mt-3 text-sm leading-6 text-muted">Nomor telepon wajib diisi. Kolom bertanda <span aria-hidden="true">*</span> wajib diisi jika Anda menambahkan pendidikan.</p>
                <div class="mt-6">
                    <label for="phone" class="mb-2 block text-sm font-medium">Nomor telepon <span aria-hidden="true" class="text-primary">*</span></label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="32" required value="{{ old('phone', data_get($payload, 'profile.phone', $profile?->phone)) }}" class="form-input" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="phone-hint{{ $errors->has('phone') ? ' phone-error' : '' }}">
                    <p id="phone-hint" class="mt-2 text-xs leading-5 text-muted">Gunakan nomor aktif yang dapat dihubungi, termasuk kode negara bila diperlukan.</p>
                    @error('phone')
                        <p id="phone-error" class="mt-2 text-sm text-primary-hover">{{ $message }} Isi nomor telepon aktif, maksimal 32 karakter.</p>
                    @enderror
                </div>
            </section>

            <section aria-labelledby="education-heading" class="mt-10">
                <h2 id="education-heading" class="text-xl font-semibold">Riwayat pendidikan</h2>
                <p id="education-hint" class="mt-3 text-sm leading-6 text-muted">Opsional. Anda dapat menyimpan profil tanpa riwayat pendidikan. Jika ditambahkan, lengkapi institusi, jenjang, jurusan, dan status pada setiap baris.</p>
                @if ($institutions->isEmpty())
                    <p class="mt-4 rounded-card bg-surface p-4 text-sm leading-6">Pilihan institusi belum tersedia. Anda tetap dapat menyimpan profil tanpa pendidikan. Hapus baris pendidikan yang belum dapat dilengkapi sebelum menyimpan.</p>
                @endif
                @error('educations')
                    <p id="educations-error" class="mt-3 text-sm text-primary-hover">{{ $message }} Lengkapi setiap baris atau hapus baris yang tidak diperlukan.</p>
                @enderror
                <div id="education-rows" class="mt-6 space-y-6" aria-describedby="education-hint{{ $errors->has('educations') ? ' educations-error' : '' }}">
                    @foreach ($educationRows as $index => $education)
                        @include('applications._education', ['index' => $index, 'number' => $loop->iteration, 'education' => $education])
                    @endforeach
                </div>
                <p id="education-empty" class="mt-4 text-sm text-muted" @if (count($educationRows)) hidden @endif>Belum ada riwayat pendidikan pada formulir ini.</p>
                <div class="mt-5">
                    <x-button id="add-education" type="button" :disabled="$institutions->isEmpty()" aria-describedby="education-hint">Tambah pendidikan</x-button>
                </div>
                <p id="education-announcement" role="status" class="sr-only"></p>
                <noscript><p class="mt-4 text-sm text-primary-hover">Aktifkan JavaScript untuk menambah atau menghapus baris pendidikan dan melindungi perubahan yang belum disimpan. Simpan profil sebelum mengunggah dokumen atau mengirim lamaran.</p></noscript>
            </section>
            <div class="mt-8 border-t border-line pt-6">
                <x-button type="submit" variant="primary" class="w-full sm:w-auto">Simpan profil dan pendidikan</x-button>
                <p class="mt-3 text-xs leading-5 text-muted">Simpan kembali setiap kali Anda mengubah nomor telepon atau riwayat pendidikan.</p>
            </div>
        </form>

        <aside class="min-w-0 space-y-8 lg:col-span-1">
            <section aria-labelledby="documents-heading" class="card p-5 sm:p-6">
                <h2 id="documents-heading" class="text-xl font-semibold">Dokumen pendukung</h2>
                <p class="mt-3 text-sm leading-6 text-muted">Unggah satu PDF per jenis dokumen, maksimal 5 MB. Unggahan baru menggantikan berkas sebelumnya untuk jenis yang sama.</p>
                <div class="mt-6 space-y-6">
                    @forelse ($vacancy->documentRequirements as $requirement)
                        @php
                            $typeId = $requirement->document_type_id;
                            $document = $documents->get($typeId);
                            $missing = $missingDocuments->contains('document_type_id', $typeId);
                            $uploadError = (string) old('document_type_id', '') === (string) $typeId;
                            $fileError = $uploadError && $errors->has('file');
                            $typeError = $uploadError && $errors->has('document_type_id');
                            $missingError = $errors->has('documents.'.$typeId);
                            $byteSize = (int) ($document->byte_size ?? 0);
                            $size = $byteSize >= 1048576 ? number_format($byteSize / 1048576, 2, ',', '.').' MB' : number_format($byteSize / 1024, 1, ',', '.').' KB';
                        @endphp
                        <form action="{{ route('apply.documents', $application->id) }}" method="POST" enctype="multipart/form-data" data-protected-form class="border-t border-line pt-5" aria-labelledby="document-{{ $typeId }}-label" @if ($typeError) aria-describedby="document-{{ $typeId }}-type-error" @endif>
                            @csrf
                            <input type="hidden" name="lock_version" value="{{ $draft->lock_version }}">
                            <input type="hidden" name="document_type_id" value="{{ $typeId }}">
                            <label id="document-{{ $typeId }}-label" for="document-{{ $typeId }}" class="block text-sm font-semibold">{{ $requirement->documentType->label }}</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-tag :variant="$requirement->required ? 'default' : 'neutral'">{{ $requirement->required ? 'Wajib' : 'Opsional' }}</x-tag>
                                @if ($missing)
                                    <x-tag>Belum diunggah</x-tag>
                                @elseif ($document)
                                    <x-tag variant="green">Sudah diunggah</x-tag>
                                @endif
                            </div>
                            @if ($document)
                                <p class="mt-3 break-words text-sm leading-6">{{ data_get($payload, 'documents.'.$typeId.'.original_name') ?: 'Berkas tersimpan; nama asli tidak tersedia.' }}<span class="block text-xs text-muted">{{ $size }}</span></p>
                            @endif
                            <input id="document-{{ $typeId }}" name="file" type="file" accept="application/pdf,.pdf" required class="form-input mt-4 min-w-0 text-sm" aria-invalid="{{ $fileError || $missingError ? 'true' : 'false' }}" aria-describedby="document-{{ $typeId }}-hint{{ $fileError ? ' document-'.$typeId.'-file-error' : '' }}{{ $missingError ? ' document-'.$typeId.'-missing-error' : '' }}">
                            <p id="document-{{ $typeId }}-hint" class="mt-2 text-xs leading-5 text-muted">{{ $document ? 'Pilih PDF pengganti' : 'Pilih berkas PDF' }}, maksimal 5 MB. Pilihan berkas belum tersimpan sampai Anda menekan Unggah.</p>
                            @if ($fileError)
                                <p id="document-{{ $typeId }}-file-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first('file') }} Pilih kembali PDF berukuran maksimal 5 MB.</p>
                            @endif
                            @if ($typeError)
                                <p id="document-{{ $typeId }}-type-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first('document_type_id') }} Muat ulang draf untuk mendapatkan daftar dokumen terbaru.</p>
                            @endif
                            @if ($missingError)
                                <p id="document-{{ $typeId }}-missing-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first('documents.'.$typeId) }} Unggah dokumen ini sebelum mengirim lamaran.</p>
                            @endif
                            <x-button type="submit" data-protected-action aria-describedby="profile-dirty-notice" class="mt-4 w-full">{{ $document ? 'Unggah pengganti' : 'Unggah dokumen' }}</x-button>
                        </form>
                    @empty
                        <p class="text-sm leading-6 text-muted">Tidak ada dokumen yang perlu diunggah untuk posisi ini.</p>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="submit-heading" class="card p-5 sm:p-6">
                <h2 id="submit-heading" class="text-xl font-semibold">Kirim lamaran</h2>
                <p id="submit-hint" class="mt-3 text-sm leading-6 text-muted">Periksa kembali profil, pendidikan, dan dokumen Anda. Setelah dikirim, draf tidak dapat diubah.</p>
                @if (! $readyToSubmit)
                    <p id="submit-blocker" class="mt-4 text-sm leading-6 text-primary-hover">{{ ! $profileComplete ? 'Simpan nomor telepon pada profil. ' : '' }}{{ $missingDocuments->isNotEmpty() ? 'Unggah semua dokumen wajib yang masih belum tersedia. ' : '' }}Tombol kirim tersedia setelah data wajib lengkap.</p>
                @endif
                <form action="{{ route('apply.submit', $application->id) }}" method="POST" data-protected-form class="mt-5">
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $draft->lock_version }}">
                    <x-button type="submit" variant="primary" full data-protected-action :disabled="! $readyToSubmit" aria-describedby="submit-hint profile-dirty-notice{{ ! $readyToSubmit ? ' submit-blocker' : '' }}">Kirim lamaran</x-button>
                </form>
            </section>
        </aside>
    </div>
</div>

<template id="education-template">
    @include('applications._education', ['index' => '__ROW__', 'number' => '', 'education' => [], 'template' => true])
</template>
<script>
(() => {
    const profile = document.getElementById('profile-form');
    const rows = document.getElementById('education-rows');
    const addButton = document.getElementById('add-education');
    const notice = document.getElementById('profile-dirty-notice');
    const announcement = document.getElementById('education-announcement');
    const protectedButtons = [...document.querySelectorAll('[data-protected-action]')];
    const initiallyDisabled = new Map(protectedButtons.map(button => [button, button.disabled]));
    const snapshot = () => JSON.stringify([...new FormData(profile).entries()]);
    const initialValues = snapshot();
    const recovered = profile.dataset.recovered === 'true';
    let dirty = recovered;
    let nextIndex = Math.max(-1, ...[...rows.querySelectorAll('[name]')].map(field => Number(field.name.match(/^educations\[(\d+)\]/)?.[1] ?? -1))) + 1;

    const updateDirty = () => {
        dirty = recovered || snapshot() !== initialValues;
        notice.hidden = !dirty;
        protectedButtons.forEach(button => { button.disabled = dirty || initiallyDisabled.get(button); });
    };
    const updateRows = () => {
        const educationRows = [...rows.querySelectorAll('[data-education-row]')];
        educationRows.forEach((row, index) => {
            row.querySelector('[data-education-number]').textContent = index + 1;
            row.querySelector('[data-remove-education]').setAttribute('aria-label', `Hapus pendidikan ${index + 1}`);
        });
        document.getElementById('education-empty').hidden = educationRows.length > 0;
    };
    profile.addEventListener('input', updateDirty);
    profile.addEventListener('change', updateDirty);
    addButton.addEventListener('click', () => {
        const fragment = document.getElementById('education-template').content.cloneNode(true);
        fragment.querySelectorAll('*').forEach(element => {
            ['id', 'name', 'for', 'aria-describedby'].forEach(attribute => {
                const value = element.getAttribute(attribute);
                if (value) element.setAttribute(attribute, value.replaceAll('__ROW__', String(nextIndex)));
            });
        });
        nextIndex += 1;
        const newRow = fragment.querySelector('[data-education-row]');
        rows.append(fragment);
        updateRows();
        updateDirty();
        newRow.querySelector('select').focus();
        announcement.textContent = 'Baris pendidikan ditambahkan. Pilih institusi untuk melengkapinya.';
    });
    rows.addEventListener('click', event => {
        const removeButton = event.target.closest('[data-remove-education]');
        if (!removeButton) return;
        const row = removeButton.closest('[data-education-row]');
        const nextFocus = row.nextElementSibling?.querySelector('select') || row.previousElementSibling?.querySelector('[data-remove-education]');
        row.remove();
        updateRows();
        updateDirty();
        (nextFocus || (addButton.disabled ? document.getElementById('phone') : addButton)).focus();
        announcement.textContent = 'Baris pendidikan dihapus. Simpan profil dan pendidikan untuk menyimpan perubahan.';
    });
    document.querySelectorAll('[data-protected-form]').forEach(form => {
        form.addEventListener('submit', event => {
            updateDirty();
            if (dirty) {
                event.preventDefault();
                profile.querySelector('[type="submit"]').focus();
            }
        });
    });
    updateRows();
    updateDirty();
})();
</script>
@endsection
