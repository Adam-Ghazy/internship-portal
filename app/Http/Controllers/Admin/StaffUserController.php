<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\OrgUnit;
use App\Models\Recruitment\RecruitmentUser;
use App\Models\Recruitment\StaffAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffUserController extends Controller
{
    private const ROLES = ['admin', 'manager', 'sm'];

    private const ROLE_LABELS = [
        'admin' => 'Admin / HR',
        'manager' => 'Manager',
        'sm' => 'Senior Manager',
    ];

    public function index(): View
    {
        $users = RecruitmentUser::query()
            ->whereHas('staffAssignments')
            ->with(['staffAssignments' => fn ($assignments) => $assignments->orderByDesc('valid_from')->with('orgUnit')])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.staff.index', ['users' => $users, 'roleLabels' => self::ROLE_LABELS]);
    }

    public function create(): View
    {
        return view('admin.staff.create', ['orgUnits' => $this->orgUnits(), 'roleLabels' => self::ROLE_LABELS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($data) {
            $user = RecruitmentUser::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => $data['password'],
            ]);

            StaffAssignment::create([
                'user_id' => $user->id,
                'role_code' => $data['role_code'],
                'org_unit_id' => $data['role_code'] === 'admin' ? null : $data['org_unit_id'],
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?? null,
            ]);
        });

        return redirect()->route('staff.users.index')->with('status', 'Akun staf baru berhasil dibuat.');
    }

    public function edit(RecruitmentUser $user): View
    {
        $assignment = $user->staffAssignments()
            ->orderByRaw('valid_to IS NOT NULL, valid_from DESC')
            ->first();

        return view('admin.staff.edit', [
            'user' => $user,
            'assignment' => $assignment,
            'orgUnits' => $this->orgUnits(),
            'roleLabels' => self::ROLE_LABELS,
        ]);
    }

    public function update(Request $request, RecruitmentUser $user): RedirectResponse
    {
        $data = $this->validateData($request, $user);

        if ($user->id === $request->user()->id) {
            if ($data['disabled'] ?? false) {
                throw ValidationException::withMessages(['disabled' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }
            if ($data['role_code'] !== 'admin') {
                throw ValidationException::withMessages(['role_code' => 'Anda tidak dapat mengubah peran akun sendiri dari admin.']);
            }
            if (Carbon::parse($data['valid_from'])->isFuture()) {
                throw ValidationException::withMessages(['valid_from' => 'Tanggal mulai tidak boleh menonaktifkan akses admin Anda saat ini.']);
            }
            if (filled($data['valid_to'] ?? null) && Carbon::parse($data['valid_to'])->lte(now())) {
                throw ValidationException::withMessages(['valid_to' => 'Tanggal berakhir tidak boleh menonaktifkan akses admin Anda saat ini.']);
            }
        }

        DB::transaction(function () use ($user, $data) {
            $user = RecruitmentUser::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'disabled_at' => ($data['disabled'] ?? false) ? ($user->disabled_at ?? now()) : null,
            ]);

            if (filled($data['password'] ?? null)) {
                $user->update(['password' => $data['password']]);
            }

            $assignmentData = [
                'role_code' => $data['role_code'],
                'org_unit_id' => $data['role_code'] === 'admin' ? null : $data['org_unit_id'],
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?? null,
            ];

            $assignment = ($data['assignment_id'] ?? null)
                ? StaffAssignment::where('user_id', $user->id)->whereKey($data['assignment_id'])->lockForUpdate()->firstOrFail()
                : null;

            $scopeChanged = $assignment && (
                $assignment->role_code !== $assignmentData['role_code']
                || $assignment->org_unit_id != $assignmentData['org_unit_id']
            );
            $reviews = $assignment
                ? DB::table('recruitment.application_reviews')->where('staff_assignment_id', $assignment->id)
                : null;
            $hasReviews = $reviews && (clone $reviews)->exists();
            $hasOpenReviews = $reviews && (clone $reviews)->whereIn('status', ['pending', 'blocked'])->exists();
            $endsAt = $assignmentData['valid_to'] ? Carbon::parse($assignmentData['valid_to']) : null;
            $startsAt = Carbon::parse($assignmentData['valid_from']);

            if ($hasOpenReviews && ($scopeChanged || $startsAt->isFuture() || ($endsAt && $endsAt->lte(now())))) {
                throw ValidationException::withMessages([
                    'assignment_id' => 'Penugasan masih memiliki ulasan tertunda. Selesaikan atau alihkan ulasan sebelum mengubah peran, unit, atau masa aktif.',
                ]);
            }

            if ($scopeChanged && $hasReviews) {
                $boundary = now();
                if ($assignment->valid_from->gte($boundary)) {
                    throw ValidationException::withMessages(['valid_from' => 'Penugasan yang memiliki riwayat belum dapat ditutup. Periksa tanggal mulai aktif.']);
                }
                if ($assignment->valid_to === null || $assignment->valid_to->gt($boundary)) {
                    $assignment->update(['valid_to' => $boundary]);
                }
                $assignmentData['valid_from'] = $startsAt->max($boundary)->toDateTimeString();
                if ($endsAt && $endsAt->lte($assignmentData['valid_from'])) {
                    throw ValidationException::withMessages(['valid_to' => 'Tanggal berakhir penugasan baru harus setelah waktu perubahan.']);
                }
                $assignment = null;
            }

            if ($assignmentData['valid_to'] === null && StaffAssignment::where('user_id', $user->id)
                ->where('role_code', $assignmentData['role_code'])
                ->where('org_unit_id', $assignmentData['org_unit_id'])
                ->when($assignment, fn ($query) => $query->where('id', '<>', $assignment->id))
                ->whereNull('valid_to')->exists()) {
                throw ValidationException::withMessages(['assignment_id' => 'Penugasan aktif untuk peran dan unit ini sudah ada. Pilih penugasan tersebut untuk diedit.']);
            }

            if ($assignment) {
                $assignment->update($assignmentData);
            } else {
                StaffAssignment::create(['user_id' => $user->id] + $assignmentData);
            }
        });

        return redirect()->route('staff.users.index')->with('status', 'Data akun staf berhasil diperbarui.');
    }

    public function toggle(Request $request, RecruitmentUser $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['disabled' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->update(['disabled_at' => $user->disabled_at === null ? now() : null]);

        return back()->with('status', $user->disabled_at === null ? 'Akun diaktifkan kembali.' : 'Akun dinonaktifkan.');
    }

    private function validateData(Request $request, ?RecruitmentUser $user = null): array
    {
        $emailRule = Rule::unique(RecruitmentUser::class, 'email');
        $usernameRule = Rule::unique(RecruitmentUser::class, 'username');
        if ($user !== null) {
            $emailRule = $emailRule->ignore($user->id);
            $usernameRule = $usernameRule->ignore($user->id);
        }

        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }

        if (is_string($request->input('username'))) {
            $request->merge(['username' => trim($request->input('username'))]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:254', $emailRule],
            'username' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/', $usernameRule],
            'password' => $user === null
                ? ['required', 'string', 'min:8', 'max:72', 'confirmed']
                : ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'role_code' => ['required', Rule::in(self::ROLES)],
            'org_unit_id' => ['required_if:role_code,manager,sm', 'nullable', 'integer', Rule::exists(OrgUnit::class, 'id')->whereNull('archived_at')],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'disabled' => ['nullable', 'boolean'],
            'assignment_id' => ['nullable', 'integer', Rule::exists(StaffAssignment::class, 'id')->where('user_id', $user?->id)],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 160 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 254 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 64 karakter.',
            'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Kata sandi wajib diisi untuk akun baru.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.max' => 'Kata sandi maksimal 72 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'role_code.required' => 'Pilih peran staf.',
            'role_code.in' => 'Peran staf tidak valid.',
            'org_unit_id.required_if' => 'Pilih unit organisasi untuk peran Manager atau Senior Manager.',
            'org_unit_id.exists' => 'Unit organisasi tidak valid.',
            'valid_from.required' => 'Tanggal mulai aktif wajib diisi.',
            'valid_from.date' => 'Tanggal mulai aktif tidak valid.',
            'valid_to.date' => 'Tanggal berakhir aktif tidak valid.',
            'valid_to.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
            'assignment_id.exists' => 'Penugasan tidak valid.',
        ]);
    }

    private function orgUnits()
    {
        return OrgUnit::whereNull('archived_at')->orderBy('name')->get();
    }
}
