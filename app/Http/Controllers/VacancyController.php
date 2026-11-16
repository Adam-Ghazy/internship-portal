<?php

namespace App\Http\Controllers;

use App\Models\Recruitment\Vacancy;
use Illuminate\Contracts\View\View;

class VacancyController extends Controller
{
    public function index(): View
    {
        $vacancies = Vacancy::where('status', 'published')
            ->with(['position', 'orgUnit', 'period', 'requirements'])
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(12);

        return view('home', compact('vacancies'));
    }

    public function show(string $slug): View
    {
        $vacancy = Vacancy::where('status', 'published')
            ->where('slug', $slug)
            ->with(['position', 'orgUnit', 'period', 'program', 'requirements', 'documentRequirements.documentType'])
            ->firstOrFail();

        return view('vacancies.show', compact('vacancy'));
    }
}
