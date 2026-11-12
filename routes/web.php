<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\ApplyController;
use App\Http\Controllers\StaffController;
use App\Models\Recruitment\Vacancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function (): View {
    $vacancies = Vacancy::where('status', 'published')
        ->with(['position', 'orgUnit', 'period', 'program', 'requirements'])
        ->orderBy('published_at', 'desc')
        ->get();

    return view('home', compact('vacancies'));
})->name('home');

Route::get('/posisi/{slug}', function (string $slug): View {
    $vacancy = Vacancy::where('status', 'published')
        ->where('slug', $slug)
        ->with(['position', 'orgUnit', 'period', 'program', 'requirements', 'documentRequirements.documentType'])
        ->firstOrFail();

    return view('vacancies.show', compact('vacancy'));
})->name('vacancies.show');

Route::middleware('auth:web')->group(function (): void {
    Route::get('/posisi/{slug}/lamar', [ApplyController::class, 'create'])->name('apply.create');
    Route::get('/lamaran', [ApplyController::class, 'index'])->name('applications.index');
    Route::get('/lamaran/{application}', [ApplyController::class, 'show'])->whereNumber('application')->name('applications.show');
    Route::get('/lamaran/{application}/tarik', [ApplyController::class, 'confirmWithdrawal'])->whereNumber('application')->name('applications.withdraw.confirm');
    Route::post('/lamaran/{application}/tarik', [ApplyController::class, 'withdraw'])->whereNumber('application')->name('applications.withdraw');
    Route::post('/lamaran/{application}/profil', [ApplyController::class, 'saveProfile'])->whereNumber('application')->name('apply.profile');
    Route::post('/lamaran/{application}/dokumen', [ApplyController::class, 'uploadDocument'])->whereNumber('application')->name('apply.documents');
    Route::post('/lamaran/{application}/submit', [ApplyController::class, 'submit'])->whereNumber('application')->name('apply.submit');
});

Route::prefix('staf')->name('staff.')->middleware(['auth:web', 'staff.role:admin,manager,sm'])->group(function (): void {
    Route::get('/', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/lamaran', [StaffController::class, 'index'])->name('index');
    Route::get('/lamaran/{application}', [StaffController::class, 'show'])->whereNumber('application')->name('show');
    Route::get('/lamaran/{application}/dokumen/{document}', [StaffController::class, 'download'])
        ->whereNumber('application')->whereNumber('document')->name('documents');
    Route::post('/lamaran/{application}/assign', [StaffController::class, 'assign'])
        ->middleware('staff.role:admin')->whereNumber('application')->name('assign');
    Route::post('/lamaran/{application}/review', [StaffController::class, 'review'])
        ->middleware('staff.role:manager,sm')->whereNumber('application')->name('review');
    Route::post('/lamaran/{application}/putuskan', [StaffController::class, 'publish'])
        ->middleware('staff.role:admin')->whereNumber('application')->name('publish');
});

Route::prefix('staf/pengguna')->name('staff.users.')->middleware(['auth:web', 'staff.role:admin'])->group(function (): void {
    Route::get('/', [StaffUserController::class, 'index'])->name('index');
    Route::get('/create', [StaffUserController::class, 'create'])->name('create');
    Route::post('/', [StaffUserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [StaffUserController::class, 'edit'])->whereNumber('user')->name('edit');
    Route::patch('/{user}', [StaffUserController::class, 'update'])->whereNumber('user')->name('update');
    Route::post('/{user}/status', [StaffUserController::class, 'toggle'])->whereNumber('user')->name('toggle');
});

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::get('/dashboard', function (Request $request): View {
    $user = $request->user('web');
    $assignments = $user->activeStaffAssignments()->with('orgUnit')->orderBy('role_code')->get();

    $applications = collect();
    $education = null;
    $profileComplete = false;

    if ($user->isApplicant() && $user->applicantProfile) {
        $profile = $user->applicantProfile;

        $applications = DB::table('recruitment.applications as a')
            ->join('recruitment.vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->join('recruitment.internship_positions as p', 'p.id', '=', 'v.position_id')
            ->join('recruitment.org_units as u', 'u.id', '=', 'v.org_unit_id')
            ->join('recruitment.recruitment_periods as rp', 'rp.id', '=', 'v.period_id')
            ->where('a.applicant_profile_id', $profile->id)
            ->select('a.*', 'v.slug', 'p.title as position_title', 'u.name as unit_name', 'rp.title as period_title')
            ->orderByDesc('a.updated_at')->orderByDesc('a.id')->get();

        $education = DB::table('recruitment.applicant_educations as e')
            ->join('recruitment.institutions as i', 'i.id', '=', 'e.institution_id')
            ->where('e.applicant_profile_id', $profile->id)
            ->where('e.is_active', true)
            ->orderBy('e.id')
            ->select('e.major', 'e.education_level', 'i.name as institution_name')
            ->first();

        $profileComplete = filled($profile->phone) && $education !== null;
    }

    return view('dashboard', compact('user', 'assignments', 'applications', 'education', 'profileComplete'));
})->middleware('auth:web')->name('dashboard');
