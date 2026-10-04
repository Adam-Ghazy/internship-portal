<?php

namespace App\Http\Controllers;

use App\Models\Recruitment\ApplicantProfile;
use App\Models\Recruitment\Vacancy;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplyController extends Controller
{
    private const EDUCATION_LEVELS = ['SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];
    private const WITHDRAWABLE_STAGES = ['draft', 'submitted', 'needs_revision', 'administrative_review'];

    public function create(Request $request, string $slug)
    {
        $profile = $this->profile($request);
        $application = DB::transaction(function () use ($profile, $slug, $request) {
            // Serialize creation for this profile before checking the unique profile/vacancy pair.
            $profile->newQuery()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $vacancy = Vacancy::where('slug', $slug)->firstOrFail();
            $application = DB::table('recruitment.applications')
                ->where('applicant_profile_id', $profile->id)->where('vacancy_id', $vacancy->id)
                ->lockForUpdate()->first();
            if ($application) {
                $this->assertEditable($application);
            }
            $this->openVacancy($vacancy->id);
            if (! $application) {
                $id = DB::table('recruitment.applications')->insertGetId([
                    // The existing schema requires a unique, non-null value even for drafts.
                    'public_reference' => 'DRAFT-'.Str::uuid(),
                    'applicant_profile_id' => $profile->id,
                    'applicant_user_id' => $request->user()->id,
                    'vacancy_id' => $vacancy->id,
                    'period_id' => $vacancy->period_id,
                    'stage' => 'draft',
                ]);
                DB::table('recruitment.application_drafts')->insert([
                    'application_id' => $id, 'payload' => '{}', 'lock_version' => 0,
                ]);
                $application = DB::table('recruitment.applications')->find($id);
            }

            return $application;
        });
        $vacancy = Vacancy::with(['position', 'orgUnit', 'period', 'documentRequirements.documentType'])
            ->findOrFail($application->vacancy_id);
        $draft = DB::table('recruitment.application_drafts')->where('application_id', $application->id)->first();
        $payload = json_decode($draft->payload, true);
        $educations = $payload['educations'] ?? DB::table('recruitment.applicant_educations')
            ->where('applicant_profile_id', $profile->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $institutions = DB::table('recruitment.institutions')->whereNull('archived_at')->orderBy('name')->get();
        $educationLevels = self::EDUCATION_LEVELS;
        $documents = $this->draftDocuments($application->id);
        $profileComplete = filled($payload['profile']['phone'] ?? null);
        $missingDocuments = $vacancy->documentRequirements->filter(fn ($requirement) =>
            $requirement->required && (! isset($documents[$requirement->document_type_id])
                || $documents[$requirement->document_type_id]->scan_status !== 'clean'));

        return view('applications.create', compact('application', 'vacancy', 'profile', 'draft', 'payload',
            'educations', 'institutions', 'educationLevels', 'documents', 'profileComplete', 'missingDocuments'));
    }

    public function index(Request $request)
    {
        $profile = $this->profile($request);
        $applications = DB::table('recruitment.applications as a')
            ->join('recruitment.vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->join('recruitment.internship_positions as p', 'p.id', '=', 'v.position_id')
            ->join('recruitment.org_units as u', 'u.id', '=', 'v.org_unit_id')
            ->where('a.applicant_profile_id', $profile->id)
            ->select('a.*', 'v.slug', 'p.title as position_title', 'u.name as unit_name')
            ->orderByDesc('a.updated_at')->orderByDesc('a.id')->get();

        return view('applications.index', [
            'applications' => $applications,
            'withdrawableStages' => self::WITHDRAWABLE_STAGES,
        ]);
    }

    public function show(Request $request, int $application)
    {
        $application = $this->ownedApplication($request, $application);
        if (in_array($application->stage, ['draft', 'needs_revision'], true)) {
            return redirect()->route('apply.create', Vacancy::findOrFail($application->vacancy_id)->slug);
        }
        $submission = DB::table('recruitment.application_submissions')->find($application->current_submission_id);
        $profileSnapshot = json_decode($submission->profile_snapshot, true);
        $educationSnapshot = json_decode($submission->education_snapshot, true);
        $vacancySnapshot = json_decode($submission->vacancy_snapshot, true);
        $documents = DB::table('recruitment.application_documents as d')
            ->join('recruitment.stored_files as f', 'f.id', '=', 'd.stored_file_id')
            ->join('recruitment.document_types as t', 't.id', '=', 'd.document_type_id')
            ->where('d.submission_id', $submission->id)->select('d.document_type_id', 't.label', 'f.*')->get();

        return view('applications.show', compact('application', 'submission', 'profileSnapshot',
            'educationSnapshot', 'vacancySnapshot', 'documents'));
    }

    public function confirmWithdrawal(Request $request, int $application)
    {
        $application = $this->ownedApplication($request, $application);
        $this->assertWithdrawable($application);
        $vacancy = Vacancy::with(['position', 'orgUnit'])->findOrFail($application->vacancy_id);

        return view('applications.withdraw', compact('application', 'vacancy'));
    }

    public function withdraw(Request $request, int $application)
    {
        DB::transaction(function () use ($request, $application) {
            $profile = $this->profile($request);
            $profile->newQuery()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $owned = $this->ownedApplication($request, $application);
            // Match the DB function's vacancy -> application lock order.
            DB::table('recruitment.vacancies')->where('id', $owned->vacancy_id)->lockForUpdate()->first();
            $owned = $this->ownedApplication($request, $application, true);
            $this->assertWithdrawable($owned);

            if ($owned->stage === 'draft') {
                DB::table('recruitment.application_draft_documents')->where('application_id', $owned->id)->delete();
                DB::table('recruitment.application_drafts')->where('application_id', $owned->id)->delete();
                DB::table('recruitment.applications')->where('id', $owned->id)->delete();
            } else {
                DB::select('SELECT recruitment.withdraw_application(?, ?)', [$owned->id, $request->user()->id]);
            }
        });

        return redirect()->route('applications.index')->with('status', 'Lamaran berhasil ditarik.');
    }

    public function saveProfile(Request $request, int $application)
    {
        DB::transaction(function () use ($request, $application) {
            [$application, $draft, $vacancy] = $this->editableDraft($request, $application);
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'string', 'max:32', 'regex:/^[+0-9][0-9 ()-]{5,31}$/'],
                'educations' => ['sometimes', 'array'],
                'educations.*' => ['array'],
                'educations.*.institution_id' => ['required', 'integer', Rule::in(DB::table('recruitment.institutions')->whereNull('archived_at')->pluck('id')->all())],
                'educations.*.education_level' => ['required', Rule::in(self::EDUCATION_LEVELS)],
                'educations.*.major' => ['required', 'string', 'max:160'],
                'educations.*.is_active' => ['required', 'boolean'],
                'educations.*.grade' => ['nullable', 'required_with:educations.*.grade_scale', 'numeric', 'decimal:0,2', 'min:0', 'max:9999.99', 'lte:educations.*.grade_scale'],
                'educations.*.grade_scale' => ['nullable', 'required_with:educations.*.grade', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999.99'],
            ], [
                'required' => ':attribute belum diisi. Lengkapi kolom ini lalu simpan kembali.',
                'string' => ':attribute harus berupa teks. Periksa kembali isian Anda.',
                'array' => ':attribute tidak valid. Muat ulang formulir lalu isi kembali.',
                'integer' => ':attribute tidak valid. Pilih dari daftar yang tersedia.',
                'in' => ':attribute tidak tersedia. Pilih dari daftar yang tersedia.',
                'boolean' => ':attribute tidak valid. Pilih status pendidikan yang tersedia.',
                'numeric' => ':attribute harus berupa angka. Gunakan titik untuk desimal.',
                'decimal' => ':attribute hanya boleh memiliki maksimal dua angka desimal. Bulatkan nilai lalu simpan kembali.',
                'max' => ':attribute melebihi batas :max. Kurangi nilai atau panjang isian.',
                'min' => ':attribute tidak boleh kurang dari :min. Periksa kembali nilainya.',
                'gt' => ':attribute harus lebih dari :value. Isi skala nilai yang berlaku.',
                'lte' => 'Nilai tidak boleh melebihi skala nilai. Periksa kedua kolom.',
                'required_with' => 'Nilai dan skala nilai harus diisi bersama. Isi keduanya atau kosongkan keduanya.',
                'phone.regex' => 'Nomor telepon tidak valid. Isi 6–32 karakter berupa angka, tanda +, spasi, kurung, atau tanda hubung.',
            ], [
                'phone' => 'Nomor telepon', 'educations' => 'Data pendidikan',
                'educations.*.institution_id' => 'Institusi', 'educations.*.education_level' => 'Jenjang pendidikan',
                'educations.*.major' => 'Jurusan', 'educations.*.is_active' => 'Status pendidikan',
                'educations.*.grade' => 'Nilai', 'educations.*.grade_scale' => 'Skala nilai',
            ]);
            $data = $validator->validate();
            $educations = [];
            $institutions = DB::table('recruitment.institutions')->pluck('name', 'id');
            foreach ($data['educations'] ?? [] as $row) {
                $educations[] = [
                    'institution_id' => (int) $row['institution_id'],
                    'institution_name' => $institutions[$row['institution_id']],
                    'education_level' => $row['education_level'], 'major' => $row['major'],
                    'is_active' => (bool) $row['is_active'],
                    'grade' => $row['grade'] ?? null, 'grade_scale' => $row['grade_scale'] ?? null,
                ];
            }
            DB::table('recruitment.applicant_profiles')->where('id', $application->applicant_profile_id)
                ->update(['phone' => $data['phone']]);
            DB::table('recruitment.applicant_educations')->where('applicant_profile_id', $application->applicant_profile_id)->delete();
            foreach ($educations as $row) {
                unset($row['institution_name']);
                DB::table('recruitment.applicant_educations')->insert($row + ['applicant_profile_id' => $application->applicant_profile_id]);
            }
            $payload = json_decode($draft->payload, true);
            $payload['profile'] = ['name' => $request->user()->name, 'email' => $request->user()->email, 'phone' => $data['phone']];
            $payload['educations'] = $educations;
            $this->saveDraft($application->id, $draft, $payload);
        });

        return back()->with('status', 'Profil dan pendidikan berhasil disimpan. Lanjutkan unggah dokumen.');
    }

    public function uploadDocument(Request $request, int $application)
    {
        $storedPath = null;
        try {
            DB::transaction(function () use ($request, $application, &$storedPath) {
                [$application, $draft, $vacancy] = $this->editableDraft($request, $application);
                $request->validate([
                    'document_type_id' => ['required', 'integer', Rule::in($vacancy->documentRequirements->pluck('document_type_id')->all())],
                    'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'min:0.0009765625', 'max:5120'],
                ], [
                    'document_type_id.required' => 'Jenis dokumen belum dipilih. Pilih dokumen pada formulir lamaran.',
                    'document_type_id.integer' => 'Jenis dokumen tidak valid. Muat ulang formulir lamaran.',
                    'document_type_id.in' => 'Jenis dokumen tidak diminta untuk posisi ini. Pilih tipe yang tercantum.',
                    'file.required' => 'Dokumen belum dipilih. Pilih PDF maksimal 5 MB.',
                    'file.file' => 'Dokumen tidak valid. Pilih ulang PDF maksimal 5 MB.',
                    'file.uploaded' => 'Dokumen gagal diunggah. Pilih ulang PDF maksimal 5 MB.',
                    'file.mimes' => 'Dokumen harus berformat PDF. Unggah PDF maksimal 5 MB.',
                    'file.mimetypes' => 'Isi dokumen bukan PDF. Unggah PDF maksimal 5 MB.',
                    'file.min' => 'Dokumen kosong. Unggah PDF berisi dokumen Anda, maksimal 5 MB.',
                    'file.max' => 'Ukuran dokumen melebihi 5 MB. Kompres PDF hingga maksimal 5 MB lalu unggah kembali.',
                ]);
                $file = $request->file('file');
                $type = (int) $request->input('document_type_id');
                $sha256 = hash_file('sha256', $file->getRealPath());
                $storedPath = 'private/applications/'.$application->id.'/'.$type.'-'.now()->format('YmdHisu').'.pdf';
                if (! Storage::disk('local')->putFileAs(dirname($storedPath), $file, basename($storedPath))) {
                    throw ValidationException::withMessages(['file' => 'Dokumen belum tersimpan. Coba unggah kembali PDF Anda.']);
                }
                $fileId = DB::table('recruitment.stored_files')->insertGetId([
                    'disk' => 'local', 'object_key' => $storedPath, 'uploader_id' => $request->user()->id,
                    'mime' => 'application/pdf', 'byte_size' => $file->getSize(), 'sha256' => $sha256,
                    'scan_status' => 'clean', // Simulation only; not a malware scanner.
                ]);
                DB::table('recruitment.application_draft_documents')->upsert([
                    'application_id' => $application->id, 'document_type_id' => $type,
                    'stored_file_id' => $fileId, 'source_group_document_id' => null,
                ], ['application_id', 'document_type_id'], ['stored_file_id', 'source_group_document_id']);
                $payload = json_decode($draft->payload, true);
                $payload['documents'][$type] = ['original_name' => $file->getClientOriginalName()];
                $this->saveDraft($application->id, $draft, $payload);
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $exception;
        }

        return back()->with('status', 'Dokumen berhasil diunggah. Periksa kelengkapan sebelum mengirim lamaran.');
    }

    public function submit(Request $request, int $application)
    {
        DB::transaction(function () use ($request, $application) {
            [$application, $draft, $vacancy] = $this->editableDraft($request, $application);
            $payload = json_decode($draft->payload, true);
            $errors = [];
            if (blank($payload['profile']['phone'] ?? null)) {
                $errors['phone'] = 'Profil belum lengkap. Isi nomor telepon lalu simpan profil dan pendidikan.';
            }
            $documents = $this->draftDocuments($application->id);
            foreach ($vacancy->documentRequirements as $requirement) {
                if ($requirement->required && (! isset($documents[$requirement->document_type_id])
                    || $documents[$requirement->document_type_id]->scan_status !== 'clean')) {
                    $errors['documents.'.$requirement->document_type_id] = 'Dokumen '.$requirement->documentType->label.' belum diunggah. Unggah PDF maksimal 5 MB.';
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
            $version = (int) DB::table('recruitment.application_submissions')->where('application_id', $application->id)->max('version_no') + 1;
            $reference = $application->public_reference;
            if ($version === 1) {
                do {
                    $reference = 'APP-'.strtoupper(Str::random(8));
                    // Serialize only contenders for this reference; the unique constraint remains authoritative.
                    DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$reference]);
                } while (DB::table('recruitment.applications')->where('public_reference', $reference)->exists());
            }
            $time = now();
            $submissionId = DB::table('recruitment.application_submissions')->insertGetId([
                'application_id' => $application->id, 'version_no' => $version,
                'profile_snapshot' => json_encode((object) $payload['profile'], JSON_THROW_ON_ERROR),
                'education_snapshot' => json_encode(array_values($payload['educations'] ?? []), JSON_THROW_ON_ERROR),
                'vacancy_snapshot' => json_encode([
                    'title' => $vacancy->position->title, 'unit' => $vacancy->orgUnit->name,
                    'period' => $vacancy->period->title, 'quota' => $vacancy->quota,
                ], JSON_THROW_ON_ERROR),
                'submitted_by' => $request->user()->id, 'submitted_at' => $time,
            ]);
            foreach ($documents as $document) {
                DB::table('recruitment.application_documents')->insert([
                    'submission_id' => $submissionId, 'document_type_id' => $document->document_type_id,
                    'stored_file_id' => $document->stored_file_id,
                    'source_group_document_id' => $document->source_group_document_id,
                ]);
            }
            DB::table('recruitment.applications')->where('id', $application->id)->update([
                'stage' => 'submitted', 'submitted_at' => $time, 'current_submission_id' => $submissionId,
                'public_reference' => $reference, 'updated_at' => $time,
            ]);
        });

        return redirect()->route('applications.index')->with('status', 'Lamaran berhasil dikirim.');
    }

    private function profile(Request $request): ApplicantProfile
    {
        $profile = $request->user()->applicantProfile;
        abort_unless($profile, 403, 'Akun ini tidak memiliki profil pelamar. Gunakan akun pelamar untuk melamar.');

        return $profile;
    }

    private function ownedApplication(Request $request, int $id, bool $lock = false): object
    {
        $query = DB::table('recruitment.applications')->where('id', $id)
            ->where('applicant_profile_id', $this->profile($request)->id)
            ->where('applicant_user_id', $request->user()->id);
        $application = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($application, 404);

        return $application;
    }

    private function assertEditable(object $application): void
    {
        if ($application->stage === 'withdrawn') {
            throw new HttpResponseException(redirect()->route('applications.index')
                ->withErrors(['application' => 'Anda sudah pernah mendaftar posisi ini. Lamaran ditarik dan tidak dapat dibuka kembali.']));
        }
        if (! in_array($application->stage, ['draft', 'needs_revision'], true)) {
            throw new HttpResponseException(redirect()->route('applications.index')
                ->with('status', 'Lamaran untuk posisi ini sudah dikirim. Anda tidak dapat membuat lamaran kedua atau mengubahnya.'));
        }
    }

    private function assertWithdrawable(object $application): void
    {
        if (! in_array($application->stage, self::WITHDRAWABLE_STAGES, true)) {
            throw new HttpResponseException(redirect()->route('applications.index')->withErrors([
                'application' => $application->stage === 'withdrawn'
                    ? 'Lamaran sudah ditarik dan tidak dapat ditarik kembali.'
                    : 'Lamaran pada tahap ini tidak dapat ditarik.',
            ]));
        }

        if ($application->stage === 'draft') {
            abort_if($application->submitted_at !== null || $application->current_submission_id !== null
                || DB::table('recruitment.application_submissions')->where('application_id', $application->id)->exists(),
                409, 'Lamaran yang pernah dikirim tidak dapat dihapus sebagai draft.');
        }
    }

    private function openVacancy(int $id): Vacancy
    {
        $vacancy = Vacancy::with(['position', 'orgUnit', 'documentRequirements.documentType'])->sharedLock()->findOrFail($id);
        $period = $vacancy->period()->sharedLock()->firstOrFail();
        $vacancy->setRelation('period', $period);
        if ($vacancy->status !== 'published' || $period->closes_at->lessThanOrEqualTo(now())) {
            throw new HttpResponseException(redirect()->route('applications.index')
                ->withErrors(['application' => 'Pendaftaran posisi ini sudah ditutup.']));
        }
        if ($period->opens_at->isFuture()) {
            throw new HttpResponseException(redirect()->route('applications.index')
                ->withErrors(['application' => 'Pendaftaran posisi ini belum dibuka. Kembali saat periode pendaftaran dimulai.']));
        }

        return $vacancy;
    }

    private function editableDraft(Request $request, int $id): array
    {
        $profile = $this->profile($request);
        $profile->newQuery()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
        $application = $this->ownedApplication($request, $id, true);
        $this->assertEditable($application);
        $vacancy = $this->openVacancy($application->vacancy_id);
        $draft = DB::table('recruitment.application_drafts')->where('application_id', $id)->lockForUpdate()->first();
        $request->validate(['lock_version' => ['required', 'integer', 'min:0']], [
            'lock_version.required' => 'Versi draft tidak tersedia. Muat ulang halaman lalu coba lagi.',
            'lock_version.integer' => 'Versi draft tidak valid. Muat ulang halaman lalu coba lagi.',
            'lock_version.min' => 'Versi draft tidak valid. Muat ulang halaman lalu coba lagi.',
        ]);
        if ((int) $request->input('lock_version') !== (int) $draft->lock_version) {
            throw ValidationException::withMessages(['lock_version' => 'Draft telah berubah di tab lain. Muat ulang halaman, periksa data terbaru, lalu coba lagi.']);
        }

        return [$application, $draft, $vacancy];
    }

    private function saveDraft(int $id, object $draft, array $payload): void
    {
        $updated = DB::table('recruitment.application_drafts')->where('application_id', $id)
            ->where('lock_version', $draft->lock_version)->update([
                'payload' => json_encode((object) $payload, JSON_THROW_ON_ERROR),
                'lock_version' => $draft->lock_version + 1, 'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['lock_version' => 'Draft telah berubah. Muat ulang halaman lalu coba lagi.']);
        }
        DB::table('recruitment.applications')->where('id', $id)->update(['updated_at' => now()]);
    }

    private function draftDocuments(int $id)
    {
        return DB::table('recruitment.application_draft_documents as d')
            ->join('recruitment.stored_files as f', 'f.id', '=', 'd.stored_file_id')
            ->join('recruitment.document_types as t', 't.id', '=', 'd.document_type_id')
            ->where('d.application_id', $id)->select('d.*', 't.label', 'f.mime', 'f.byte_size', 'f.object_key', 'f.scan_status')
            ->get()->keyBy('document_type_id');
    }
}
