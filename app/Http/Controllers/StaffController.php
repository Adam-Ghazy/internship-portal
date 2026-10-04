<?php

namespace App\Http\Controllers;

use App\Models\Recruitment\Application;
use App\Models\Recruitment\ApplicationReview;
use App\Models\Recruitment\StaffAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    private const STAGES = [
        'submitted' => 'Menunggu penyaringan',
        'needs_revision' => 'Perlu revisi',
        'manager_review' => 'Review manager',
        'sm_review' => 'Review SM',
        'decided' => 'Sudah diputuskan',
    ];

    public function dashboard(Request $request)
    {
        $isAdmin = $this->isAdmin($request);
        $counts = $this->visibleApplications($request)->whereIn('stage', array_keys(self::STAGES))
            ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage');

        return view('staff.dashboard', ['counts' => $counts, 'stageLabels' => self::STAGES, 'isAdmin' => $isAdmin]);
    }

    public function index(Request $request)
    {
        $isAdmin = $this->isAdmin($request);
        $query = $this->visibleApplications($request);
        if ($isAdmin) {
            $query->whereIn('stage', ['submitted', 'decided']);
        } else {
            $query->whereHas('reviews', function (Builder $review) use ($request) {
                $this->pendingForActor($review, $request);
            });
        }
        $data = $request->validate(['stage' => ['nullable', Rule::in(array_keys(self::STAGES))]]);
        $stage = $data['stage'] ?? null;
        if ($stage) {
            $query->where('stage', $stage);
        }
        $applications = $query->with(['vacancy.position', 'vacancy.orgUnit', 'applicant', 'currentSubmission'])
            ->orderBy('submitted_at')->orderBy('id')->paginate(15)->withQueryString();
        $assignments = $isAdmin
            ? $this->eligibleAssignments()->whereIn('org_unit_id', $applications->pluck('vacancy.org_unit_id'))
                ->with('user')->get()->groupBy('org_unit_id')
            : collect();

        return view('staff.index', compact('applications', 'assignments', 'isAdmin', 'stage') + ['stageLabels' => self::STAGES]);
    }

    public function show(Request $request, int $application)
    {
        $application = $this->visibleApplications($request)->with([
            'vacancy.position', 'vacancy.orgUnit', 'applicant', 'currentSubmission',
        ])->findOrFail($application);
        $submission = $application->currentSubmission;
        $documents = $this->documents($application)->get();
        $reviews = DB::table('recruitment.application_reviews as r')
            ->join('recruitment.users as u', 'u.id', '=', 'r.assignee_id')
            ->where('r.application_id', $application->id)->select('r.*', 'u.name as reviewer_name')->orderBy('r.id')->get();
        $events = DB::table('recruitment.application_events as e')
            ->leftJoin('recruitment.users as u', 'u.id', '=', 'e.actor_id')
            ->where('e.application_id', $application->id)->select('e.*', 'u.name as actor_name')
            ->orderBy('e.occurred_at')->orderBy('e.id')->get();
        $pendingReview = $this->pendingForActor($application->reviews()->getQuery(), $request)->first();
        $publication = DB::table('recruitment.application_publications')->where('application_id', $application->id)->first();
        $isAdmin = $this->isAdmin($request);
        $assignments = $isAdmin ? $this->eligibleAssignments()->where('org_unit_id', $application->vacancy->org_unit_id)
            ->with('user')->get()->groupBy('org_unit_id') : collect();

        return view('staff.show', compact('application', 'submission', 'documents', 'reviews', 'events',
            'pendingReview', 'publication', 'isAdmin', 'assignments') + ['stageLabels' => self::STAGES]);
    }

    public function assign(Request $request, int $application)
    {
        abort_unless($this->isAdmin($request), 403);
        $data = $request->validate([
            'manager_assignment_id' => ['required', 'integer'],
            'sm_assignment_id' => ['required', 'integer', 'different:manager_assignment_id'],
        ], [
            'required' => 'Pilih reviewer untuk kedua tahap.',
            'integer' => 'Penugasan reviewer tidak valid.',
            'different' => 'Manager dan SM harus orang yang berbeda.',
        ]);
        DB::transaction(function () use ($request, $application, $data) {
            $application = $this->lockApplication($request, $application);
            if ($application->stage !== 'submitted' || ! $application->current_submission_id) {
                throw ValidationException::withMessages(['assignment' => 'Lamaran tidak lagi menunggu penyaringan. Muat ulang daftar.']);
            }
            $selected = [];
            foreach (['manager', 'sm'] as $role) {
                $selected[$role] = $this->eligibleAssignments()->whereKey($data[$role.'_assignment_id'])
                    ->where('role_code', $role)->where('org_unit_id', $application->vacancy->org_unit_id)
                    ->sharedLock()->first();
                if (! $selected[$role]) {
                    throw ValidationException::withMessages([$role.'_assignment_id' => 'Pilih reviewer dengan penugasan aktif di unit lamaran ini.']);
                }
            }
            if ($selected['manager']->user_id === $selected['sm']->user_id) {
                throw ValidationException::withMessages(['sm_assignment_id' => 'Manager dan SM harus orang yang berbeda.']);
            }
            foreach ($selected as $role => $assignment) {
                DB::table('recruitment.application_reviews')->insert([
                    'application_id' => $application->id, 'vacancy_id' => $application->vacancy_id,
                    'org_unit_id' => $application->vacancy->org_unit_id, 'stage' => $role,
                    'assignee_id' => $assignment->user_id, 'staff_assignment_id' => $assignment->id,
                    'revision_no' => 1, 'status' => $role === 'manager' ? 'pending' : 'blocked',
                    'based_on_submission_id' => $application->current_submission_id,
                    'assigned_by' => $request->user()->id,
                ]);
            }
            DB::table('recruitment.applications')->where('id', $application->id)
                ->update(['stage' => 'manager_review', 'updated_at' => now()]);
            DB::table('recruitment.application_events')->insert([
                'application_id' => $application->id, 'actor_id' => $request->user()->id, 'event_type' => 'review.assigned',
            ]);
        });

        return redirect()->route('staff.show', $application)->with('status', 'Reviewer ditugaskan. Lamaran menunggu review manager.');
    }

    public function review(Request $request, int $application)
    {
        $this->databaseAction(function () use ($request, $application) {
            $application = $this->lockApplication($request, $application);
            $review = $this->pendingForActor($application->reviews()->getQuery(), $request)->lockForUpdate()->first();
            if (! $review) {
                throw ValidationException::withMessages(['review' => 'Tidak ada review aktif untuk Anda. Muat ulang halaman.']);
            }
            $outcomes = $review->stage === 'manager' ? ['recommended', 'not_recommended'] : ['accepted', 'rejected'];
            $data = $request->validate([
                'outcome' => ['required', Rule::in($outcomes)],
                'note' => ['required', 'string', 'max:5000'],
            ], [
                'outcome.required' => 'Pilih hasil review.', 'outcome.in' => 'Hasil review tidak sesuai peran Anda.',
                'note.required' => 'Catatan wajib diisi untuk semua hasil review.',
                'note.string' => 'Catatan harus berupa teks.', 'note.max' => 'Catatan maksimal 5.000 karakter.',
            ]);
            DB::select('SELECT recruitment.complete_review(?, ?, ?, ?)', [
                $review->id, $request->user()->id, $data['outcome'], $data['note'],
            ]);
        });

        return redirect()->route('staff.show', $application)->with('status', 'Review berhasil disimpan. Tahap lamaran telah diperbarui.');
    }

    public function publish(Request $request, int $application)
    {
        abort_unless($this->isAdmin($request), 403);
        $data = $request->validate(['message' => ['required', 'string', 'min:10', 'max:5000']], [
            'message.required' => 'Pesan keputusan wajib diisi.', 'message.string' => 'Pesan keputusan harus berupa teks.',
            'message.min' => 'Pesan keputusan minimal 10 karakter.', 'message.max' => 'Pesan keputusan maksimal 5.000 karakter.',
        ]);
        $this->databaseAction(function () use ($request, $application, $data) {
            $application = $this->lockApplication($request, $application);
            if ($application->stage !== 'decided') {
                throw ValidationException::withMessages(['message' => 'Keputusan SM belum selesai. Publikasi belum dapat dilakukan.']);
            }
            DB::select('SELECT recruitment.publish_decision(?, ?, ?)', [$application->id, $request->user()->id, $data['message']]);
        });

        return redirect()->route('staff.show', $application)->with('status', 'Keputusan berhasil dipublikasikan.');
    }

    public function download(Request $request, int $application, int $document)
    {
        $application = $this->visibleApplications($request)->findOrFail($application);
        $file = $this->documents($application)->where('d.id', $document)->first();
        abort_unless($file && $file->scan_status === 'clean', 404);
        abort_unless(Storage::disk($file->disk)->exists($file->object_key), 404);

        return Storage::disk($file->disk)->download($file->object_key, basename($file->object_key), ['X-Content-Type-Options' => 'nosniff']);
    }

    private function isAdmin(Request $request): bool
    {
        return (bool) DB::selectOne("SELECT recruitment.has_staff_scope(?, 'admin', NULL) AS allowed", [$request->user()->id])->allowed;
    }

    private function visibleApplications(Request $request): Builder
    {
        $query = Application::query();
        if (! $this->isAdmin($request)) {
            $query->whereHas('vacancy', fn (Builder $vacancy) => $vacancy->whereRaw(
                "(recruitment.has_staff_scope(?, 'manager', org_unit_id) OR recruitment.has_staff_scope(?, 'sm', org_unit_id))",
                [$request->user()->id, $request->user()->id]
            ))->whereHas('reviews', fn (Builder $review) => $review->where('assignee_id', $request->user()->id)
                ->whereRaw('recruitment.has_staff_scope(?, stage, org_unit_id)', [$request->user()->id]));
        }

        return $query;
    }

    private function pendingForActor(Builder $query, Request $request): Builder
    {
        return $query->where('assignee_id', $request->user()->id)->where('status', 'pending')
            ->whereRaw('recruitment.has_staff_scope(?, recruitment.application_reviews.stage, recruitment.application_reviews.org_unit_id)', [$request->user()->id])
            ->whereExists(function ($assignment) {
                $assignment->selectRaw('1')->from('recruitment.staff_assignments as s')
                    ->whereColumn('s.id', 'recruitment.application_reviews.staff_assignment_id')
                    ->whereRaw('s.valid_from <= CURRENT_TIMESTAMP AND (s.valid_to IS NULL OR CURRENT_TIMESTAMP < s.valid_to)');
            })->whereExists(function ($application) {
                $application->selectRaw('1')->from('recruitment.applications as a')
                    ->whereColumn('a.id', 'recruitment.application_reviews.application_id')
                    ->whereColumn('a.current_submission_id', 'recruitment.application_reviews.based_on_submission_id')
                    ->whereRaw("a.stage = CASE recruitment.application_reviews.stage WHEN 'manager' THEN 'manager_review' ELSE 'sm_review' END");
            });
    }

    private function eligibleAssignments(): Builder
    {
        return StaffAssignment::query()->whereIn('role_code', ['manager', 'sm'])
            ->whereRaw('valid_from <= CURRENT_TIMESTAMP AND (valid_to IS NULL OR CURRENT_TIMESTAMP < valid_to)')
            ->whereRaw('recruitment.has_staff_scope(user_id, role_code, org_unit_id)');
    }

    private function lockApplication(Request $request, int $id): Application
    {
        $application = $this->visibleApplications($request)->findOrFail($id);
        // Match complete_review/publish_decision: vacancy, application, then review.
        $vacancy = $application->vacancy()->lockForUpdate()->firstOrFail();
        $application = $this->visibleApplications($request)->lockForUpdate()->findOrFail($id);
        $application->setRelation('vacancy', $vacancy);

        return $application;
    }

    private function documents(Application $application)
    {
        return DB::table('recruitment.application_documents as d')
            ->join('recruitment.stored_files as f', 'f.id', '=', 'd.stored_file_id')
            ->join('recruitment.document_types as t', 't.id', '=', 'd.document_type_id')
            ->where('d.submission_id', $application->current_submission_id)
            ->select('d.id', 't.label', 'f.disk', 'f.object_key', 'f.byte_size', 'f.mime', 'f.scan_status');
    }

    private function databaseAction(callable $action): void
    {
        try {
            DB::transaction($action);
        } catch (QueryException $exception) {
            // Translate only known business rejections; unexpected SQL failures remain visible to error reporting.
            $messages = [
                'quota exhausted' => 'Kuota posisi sudah penuh. Lamaran tidak dapat diterima.',
                'reviewer authorization denied' => 'Penugasan review Anda tidak lagi aktif.',
                'invalid review state' => 'Review sudah berubah. Muat ulang halaman sebelum melanjutkan.',
                'invalid manager transition' => 'Lamaran tidak lagi berada pada tahap review manager.',
                'Manager before SM required' => 'Review manager harus selesai sebelum keputusan SM.',
            ];
            foreach ($messages as $databaseMessage => $message) {
                if (str_contains($exception->getMessage(), $databaseMessage)) {
                    throw ValidationException::withMessages(['review' => $message]);
                }
            }
            throw $exception;
        }
    }
}
