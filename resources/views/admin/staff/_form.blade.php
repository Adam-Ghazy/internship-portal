@php
    $isEdit = $user !== null;
    $oldRole = old('role_code', $isEdit && $assignment ? $assignment->role_code : '');
    $oldUnit = old('org_unit_id', $isEdit && $assignment?->org_unit_id ? (string) $assignment->org_unit_id : '');
@endphp

<div class="space-y-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="name" class="mb-2 block text-sm font-medium">Nama <span class="text-muted">(wajib)</span></label>
            <input id="name" name="name" type="text" maxlength="160" required value="{{ old('name', $isEdit ? $user->name : '') }}"
                   class="form-input" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}">
            @error('name')<p id="name-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="mb-2 block text-sm font-medium">Username <span class="text-muted">(wajib)</span></label>
            <input id="username" name="username" type="text" maxlength="64" required value="{{ old('username', $isEdit ? $user->username : '') }}"
                   autocomplete="username" class="form-input" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('username') ? 'username-error' : '' }}">
            <p class="mt-2 text-xs leading-5 text-muted">Untuk masuk selain email. Huruf, angka, titik, garis bawah, atau tanda hubung.</p>
            @error('username')<p id="username-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="email" class="mb-2 block text-sm font-medium">Email <span class="text-muted">(wajib)</span></label>
        <input id="email" name="email" type="email" maxlength="254" required value="{{ old('email', $isEdit ? $user->email : '') }}"
               class="form-input" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}">
        @error('email')<p id="email-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-2 block text-sm font-medium">Kata sandi <span class="text-muted">{{ $isEdit ? '(opsional)' : '(wajib)' }}</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72"
                   {{ $isEdit ? '' : 'required' }}
                   class="form-input" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}">
            @error('password')<p id="password-error" class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-medium">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-input">
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="role_code" class="mb-2 block text-sm font-medium">Peran <span class="text-muted">(wajib)</span></label>
            <select id="role_code" name="role_code" class="form-input" required aria-invalid="{{ $errors->has('role_code') ? 'true' : 'false' }}">
                <option value="">Pilih peran</option>
                @foreach ($roleLabels as $code => $label)
                    <option value="{{ $code }}" @selected($oldRole === $code)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role_code')<p class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="org_unit_id" class="mb-2 block text-sm font-medium">Unit organisasi</label>
            <select id="org_unit_id" name="org_unit_id" class="form-input" aria-invalid="{{ $errors->has('org_unit_id') ? 'true' : 'false' }}">
                <option value="">Seluruh unit / tanpa unit</option>
                @foreach ($orgUnits as $unit)
                    <option value="{{ $unit->id }}" @selected($oldUnit === (string) $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs leading-5 text-muted">Wajib untuk Manager / Senior Manager. Kosong untuk Admin.</p>
            @error('org_unit_id')<p class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="valid_from" class="mb-2 block text-sm font-medium">Mulai aktif <span class="text-muted">(wajib)</span></label>
            <input id="valid_from" name="valid_from" type="date" required
                   value="{{ old('valid_from', $isEdit && $assignment?->valid_from ? $assignment->valid_from->format('Y-m-d') : '') }}"
                   class="form-input" aria-invalid="{{ $errors->has('valid_from') ? 'true' : 'false' }}">
            @error('valid_from')<p class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="valid_to" class="mb-2 block text-sm font-medium">Berakhir aktif (opsional)</label>
            <input id="valid_to" name="valid_to" type="date"
                   value="{{ old('valid_to', $isEdit && $assignment?->valid_to ? $assignment->valid_to->format('Y-m-d') : '') }}"
                   class="form-input" aria-invalid="{{ $errors->has('valid_to') ? 'true' : 'false' }}">
            <p class="mt-2 text-xs leading-5 text-muted">Kosongkan untuk penugasan tanpa batas akhir.</p>
            @error('valid_to')<p class="mt-2 text-sm text-primary-hover">{{ $message }}</p>@enderror
        </div>
    </div>

    @if ($isEdit)
        <input type="hidden" name="assignment_id" value="{{ $assignment?->id ?? '' }}">
        <label class="flex items-center gap-3 rounded-card border border-line bg-surface p-4">
            <input type="checkbox" name="disabled" value="1" class="h-4 w-4 rounded border-line text-primary focus:ring-primary" @checked(old('disabled', $user->disabled_at !== null))>
            <span class="text-sm">Nonaktifkan akun (blokir akses masuk)</span>
        </label>
        @error('disabled')<p class="text-sm text-primary-hover">{{ $message }}</p>@enderror
    @endif

    <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
        <x-button type="submit" variant="primary">{{ $isEdit ? 'Simpan perubahan' : 'Buat akun staf' }}</x-button>
        <x-button :href="route('staff.users.index')">Batal</x-button>
    </div>
</div>

<script>
(() => {
    const role = document.getElementById('role_code');
    const unit = document.getElementById('org_unit_id');
    const sync = () => {
        const isAdmin = role.value === 'admin';
        unit.disabled = isAdmin;
        unit.required = !isAdmin;
        if (isAdmin) unit.value = '';
    };
    role.addEventListener('change', sync);
    sync();
})();
</script>