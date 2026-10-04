@php
    $education = $education ?? [];
    $template = $template ?? false;
    $fieldPrefix = 'educations.'.$index.'.';
    $idPrefix = 'education-'.$index.'-';
@endphp
<fieldset data-education-row class="min-w-0 border-t border-line pt-6">
    <legend class="px-1 text-base font-semibold">Pendidikan <span data-education-number>{{ $number }}</span></legend>
    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="{{ $idPrefix }}institution_id" class="mb-2 block text-sm font-medium">Institusi <span aria-hidden="true" class="text-primary">*</span></label>
            <select id="{{ $idPrefix }}institution_id" name="educations[{{ $index }}][institution_id]" required class="form-input"
                aria-invalid="{{ ! $template && $errors->has($fieldPrefix.'institution_id') ? 'true' : 'false' }}"
                @if (! $template && $errors->has($fieldPrefix.'institution_id')) aria-describedby="{{ $idPrefix }}institution_id-error" @endif>
                <option value="">Pilih institusi</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->id }}" @selected((string) ($education['institution_id'] ?? '') === (string) $institution->id)>{{ $institution->name }}</option>
                @endforeach
            </select>
            @if (! $template && $errors->has($fieldPrefix.'institution_id'))
                <p id="{{ $idPrefix }}institution_id-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($fieldPrefix.'institution_id') }} Pilih institusi dari daftar, atau hapus baris pendidikan ini.</p>
            @endif
        </div>
        <div>
            <label for="{{ $idPrefix }}education_level" class="mb-2 block text-sm font-medium">Jenjang <span aria-hidden="true" class="text-primary">*</span></label>
            <select id="{{ $idPrefix }}education_level" name="educations[{{ $index }}][education_level]" required class="form-input"
                aria-invalid="{{ ! $template && $errors->has($fieldPrefix.'education_level') ? 'true' : 'false' }}"
                @if (! $template && $errors->has($fieldPrefix.'education_level')) aria-describedby="{{ $idPrefix }}education_level-error" @endif>
                <option value="">Pilih jenjang</option>
                @foreach ($educationLevels as $level)
                    <option value="{{ $level }}" @selected(($education['education_level'] ?? '') === $level)>{{ $level }}</option>
                @endforeach
            </select>
            @if (! $template && $errors->has($fieldPrefix.'education_level'))
                <p id="{{ $idPrefix }}education_level-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($fieldPrefix.'education_level') }} Pilih jenjang pendidikan Anda.</p>
            @endif
        </div>
        <div>
            <label for="{{ $idPrefix }}major" class="mb-2 block text-sm font-medium">Jurusan / program studi <span aria-hidden="true" class="text-primary">*</span></label>
            <input id="{{ $idPrefix }}major" name="educations[{{ $index }}][major]" type="text" maxlength="160" required value="{{ $education['major'] ?? '' }}" class="form-input"
                aria-invalid="{{ ! $template && $errors->has($fieldPrefix.'major') ? 'true' : 'false' }}"
                @if (! $template && $errors->has($fieldPrefix.'major')) aria-describedby="{{ $idPrefix }}major-error" @endif>
            @if (! $template && $errors->has($fieldPrefix.'major'))
                <p id="{{ $idPrefix }}major-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($fieldPrefix.'major') }} Isi jurusan atau program studi, maksimal 160 karakter.</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <label for="{{ $idPrefix }}is_active" class="mb-2 block text-sm font-medium">Status pendidikan <span aria-hidden="true" class="text-primary">*</span></label>
            <select id="{{ $idPrefix }}is_active" name="educations[{{ $index }}][is_active]" required class="form-input"
                aria-invalid="{{ ! $template && $errors->has($fieldPrefix.'is_active') ? 'true' : 'false' }}"
                @if (! $template && $errors->has($fieldPrefix.'is_active')) aria-describedby="{{ $idPrefix }}is_active-error" @endif>
                <option value="1" @selected((string) ($education['is_active'] ?? '1') === '1')>Masih menempuh pendidikan</option>
                <option value="0" @selected(in_array($education['is_active'] ?? '1', [false, 0, '0'], true))>Sudah selesai / tidak aktif</option>
            </select>
            @if (! $template && $errors->has($fieldPrefix.'is_active'))
                <p id="{{ $idPrefix }}is_active-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($fieldPrefix.'is_active') }} Pilih status pendidikan saat ini.</p>
            @endif
        </div>
        @foreach (['grade' => 'Nilai / IPK', 'grade_scale' => 'Skala nilai'] as $field => $label)
            <div>
                <label for="{{ $idPrefix.$field }}" class="mb-2 block text-sm font-medium">{{ $label }} <span class="font-normal text-muted">(opsional)</span></label>
                <input id="{{ $idPrefix.$field }}" name="educations[{{ $index }}][{{ $field }}]" type="number" step="0.01" min="{{ $field === 'grade' ? '0' : '0.01' }}" max="9999.99" inputmode="decimal" value="{{ $education[$field] ?? '' }}" class="form-input"
                    aria-invalid="{{ ! $template && $errors->has($fieldPrefix.$field) ? 'true' : 'false' }}"
                    aria-describedby="{{ $idPrefix }}grade-hint{{ ! $template && $errors->has($fieldPrefix.$field) ? ' '.$idPrefix.$field.'-error' : '' }}">
                @if (! $template && $errors->has($fieldPrefix.$field))
                    <p id="{{ $idPrefix.$field }}-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($fieldPrefix.$field) }} Isi nilai beserta skalanya; nilai tidak boleh melebihi skala.</p>
                @endif
            </div>
        @endforeach
    </div>
    <p id="{{ $idPrefix }}grade-hint" class="mt-3 text-xs leading-5 text-muted">Kosongkan keduanya jika belum ada nilai. Jika diisi, masukkan nilai dan skalanya, misalnya 3,50 dari 4,00 atau 85 dari 100.</p>
    <div class="mt-4 flex justify-end">
        <x-button type="button" data-remove-education aria-label="Hapus pendidikan {{ $number }}">Hapus pendidikan</x-button>
    </div>
</fieldset>
