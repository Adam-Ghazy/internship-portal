<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user('web');
        $assignments = $user->activeStaffAssignments()->with('orgUnit')->orderBy('role_code')->get();

        $applications = collect();
        $applicationCount = 0;
        $education = null;
        $profileComplete = false;

        if ($user->isApplicant() && $user->applicantProfile) {
            $profile = $user->applicantProfile;

            $applicationQuery = DB::table('recruitment.applications as a')
                ->join('recruitment.vacancies as v', 'v.id', '=', 'a.vacancy_id')
                ->join('recruitment.internship_positions as p', 'p.id', '=', 'v.position_id')
                ->join('recruitment.org_units as u', 'u.id', '=', 'v.org_unit_id')
                ->join('recruitment.recruitment_periods as rp', 'rp.id', '=', 'v.period_id')
                ->where('a.applicant_profile_id', $profile->id)
                ->select('a.*', 'v.slug', 'p.title as position_title', 'u.name as unit_name', 'rp.title as period_title');

            $applicationCount = (clone $applicationQuery)->count();
            $applications = $applicationQuery->orderByDesc('a.updated_at')->orderByDesc('a.id')->limit(5)->get();

            $education = DB::table('recruitment.applicant_educations as e')
                ->join('recruitment.institutions as i', 'i.id', '=', 'e.institution_id')
                ->where('e.applicant_profile_id', $profile->id)
                ->where('e.is_active', true)
                ->orderBy('e.id')
                ->select('e.major', 'e.education_level', 'i.name as institution_name')
                ->first();

            $profileComplete = filled($profile->phone) && $education !== null;
        }

        return view('dashboard', compact('user', 'assignments', 'applications', 'education', 'profileComplete', 'applicationCount'));
    }
}
