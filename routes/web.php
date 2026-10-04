<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Data lowongan sintetis (mockup V3). Nantinya diambil dari tabel recruitment.vacancies.
Route::get('/', function () {
    $vacancies = [
        [
            'icon' => 'monitor',
            'title' => 'Pengembangan Aplikasi Web',
            'unit' => 'Teknologi Informasi',
            'org' => 'PT INKA (Persero)',
            'code' => 'IT-01',
            'major' => 'Informatika / Sistem Informasi',
            'duration' => '3 bulan',
            'level' => 'D3 / D4 / S1',
            'tasks' => 'Membangun fitur aplikasi internal, menguji alur pengguna, dan menyusun dokumentasi teknis.',
        ],
        [
            'icon' => 'train',
            'title' => 'Perancangan Mekanik',
            'unit' => 'Engineering',
            'org' => 'PT INKA (Persero)',
            'code' => 'ENG-02',
            'major' => 'Teknik Mesin',
            'duration' => '3 bulan',
            'level' => 'D3 / D4 / S1',
            'tasks' => 'Membantu gambar teknik komponen dan dokumentasi rancangan bersama tim engineering.',
        ],
        [
            'icon' => 'calendar',
            'title' => 'Administrasi Keuangan',
            'unit' => 'Keuangan',
            'org' => 'PT INKA (Persero)',
            'code' => 'FIN-03',
            'major' => 'Akuntansi / Keuangan',
            'duration' => '3 bulan',
            'level' => 'D3 / D4 / S1',
            'tasks' => 'Menata dokumen transaksi dan membantu rekap administrasi keuangan.',
        ],
        [
            'icon' => 'map-pin',
            'title' => 'Dokumentasi Komunikasi',
            'unit' => 'Komunikasi Perusahaan',
            'org' => 'PT INKA (Persero)',
            'code' => 'COM-04',
            'major' => 'Ilmu Komunikasi / DKV',
            'duration' => '3 bulan',
            'level' => 'D3 / D4 / S1',
            'tasks' => 'Mendokumentasikan kegiatan dan menyiapkan materi komunikasi bersama pembimbing.',
        ],
    ];

    return view('home', compact('vacancies'));
})->name('home');

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::get('/dashboard', function (Request $request): string {
    return $request->user('web')->isStaff() ? 'Dashboard Staf' : 'Dashboard Pelamar';
})->middleware('auth:web')->name('dashboard');
