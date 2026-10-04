@php
    $unitAssignments = $assignments->get($application->vacancy?->org_unit_id, collect());
    $managers = $unitAssignments->where('role_code', 'manager');
    $seniorManagers = $unitAssignments->where('role_code', 'sm');
    $formPrefix = 'assignment-' . $application->id;
    $isOldAssignment = (string) old('_application_id') === (string) $application->id;
@endphp
@if ($managers->isEmpty() || $seniorManagers->isEmpty())
    <p class="text-sm leading-6 text-muted">Penugasan belum dapat dilakukan. Unit ini memerlukan manajer dan manajer senior dengan penugasan aktif.</p>
@else
    <form method="POST" action="{{ route('staff.assign', $application) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="_application_id" value="{{ $application->id }}">
        @foreach (['manager_assignment_id' => ['Manajer', $managers], 'sm_assignment_id' => ['Manajer senior', $seniorManagers]] as $field => [$label, $options])
            <div>
                <label for="{{ $formPrefix }}-{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }} <span class="text-muted">(wajib)</span></label>
                <select id="{{ $formPrefix }}-{{ $field }}" name="{{ $field }}" class="form-input w-full" required @if ($isOldAssignment && $errors->has($field)) aria-invalid="true" aria-describedby="{{ $formPrefix }}-{{ $field }}-error" @endif>
                    <option value="">Pilih {{ strtolower($label) }}</option>
                    @foreach ($options as $assignment)
                        <option value="{{ $assignment->id }}" @selected($isOldAssignment && (string) old($field) === (string) $assignment->id)>{{ $assignment->user?->name ?? 'Nama staf tidak tersedia' }}</option>
                    @endforeach
                </select>
                @if ($isOldAssignment && $errors->has($field))
                    <p id="{{ $formPrefix }}-{{ $field }}-error" class="mt-2 text-sm text-primary-hover">{{ $errors->first($field) }}</p>
                @endif
            </div>
        @endforeach
        <x-button type="submit" variant="primary">Tetapkan peninjau</x-button>
    </form>
@endif
