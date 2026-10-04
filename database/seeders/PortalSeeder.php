<?php

namespace Database\Seeders;

use App\Models\Recruitment\DocumentType;
use App\Models\Recruitment\InternshipPosition;
use App\Models\Recruitment\InternshipProgram;
use App\Models\Recruitment\InternshipRequest;
use App\Models\Recruitment\OrgUnit;
use App\Models\Recruitment\RecruitmentPeriod;
use App\Models\Recruitment\RecruitmentUser;
use App\Models\Recruitment\Vacancy;
use App\Models\Recruitment\VacancyDocumentRequirement;
use App\Models\Recruitment\VacancyRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $today = today();
            $startsOn = $today->copy()->subMonthNoOverflow();
            $endsOn = $today->copy()->addMonthsNoOverflow(2);
            $units = [];

            foreach (['IT' => 'Teknologi Informasi', 'ENG' => 'Engineering', 'FIN' => 'Keuangan'] as $code => $name) {
                $units[$code] = OrgUnit::updateOrCreate(['code' => $code], ['name' => $name]);
            }

            // Requests require a user even on an empty database; this is not a login account.
            $requester = RecruitmentUser::updateOrCreate(
                ['email' => 'portal-seeder@example.invalid'],
                [
                    'name' => 'Pemohon sintetis portal',
                    'password' => Str::random(64),
                    'disabled_at' => $today,
                ],
            );

            $period = RecruitmentPeriod::updateOrCreate(['code' => 'PORTAL-DEMO'], [
                'title' => 'Periode Magang Sintetis',
                'opens_at' => $startsOn,
                'closes_at' => $endsOn->copy()->endOfDay(),
            ]);
            $program = InternshipProgram::updateOrCreate(
                ['code' => 'MAGANG'],
                ['name' => 'Program Magang PT INKA (Persero)'],
            );

            $documents = [];
            foreach (['identity_card' => 'KTP', 'transcript' => 'Transkrip', 'cv' => 'CV'] as $code => $label) {
                $documents[] = DocumentType::updateOrCreate(['code' => $code], ['label' => $label]);
            }

            // Descriptions, majors and education levels retain the original catalog copy.
            $positions = [
                [
                    'code' => 'IT-01',
                    'title' => 'Pengembangan Aplikasi Web',
                    'unit' => 'IT',
                    'major' => 'Informatika / Sistem Informasi',
                    'description' => 'Membangun fitur aplikasi internal, menguji alur pengguna, dan menyusun dokumentasi teknis.',
                    'quota' => 4,
                ],
                [
                    'code' => 'ENG-02',
                    'title' => 'Perancangan Mekanik',
                    'unit' => 'ENG',
                    'major' => 'Teknik Mesin',
                    'description' => 'Membantu gambar teknik komponen dan dokumentasi rancangan bersama tim engineering.',
                    'quota' => 3,
                ],
                [
                    'code' => 'FIN-03',
                    'title' => 'Analisis Keuangan',
                    'unit' => 'FIN',
                    'major' => 'Akuntansi / Keuangan',
                    'description' => 'Menata dokumen transaksi dan membantu rekap administrasi keuangan.',
                    'quota' => 2,
                ],
                [
                    'code' => 'COM-04',
                    'title' => 'Komunikasi Korporat',
                    'unit' => 'IT',
                    'major' => 'Ilmu Komunikasi / DKV',
                    'description' => 'Mendokumentasikan kegiatan dan menyiapkan materi komunikasi bersama pembimbing.',
                    'quota' => 2,
                ],
            ];

            foreach ($positions as $index => $data) {
                $position = InternshipPosition::updateOrCreate(['code' => $data['code']], [
                    'title' => $data['title'],
                    'description' => $data['description'],
                ]);
                $request = InternshipRequest::updateOrCreate([
                    'requester_id' => $requester->id,
                    'position_id' => $position->id,
                    'period_id' => $period->id,
                    'org_unit_id' => $units[$data['unit']]->id,
                ], [
                    'requested_count' => $data['quota'],
                    'criteria' => $data['major'].'; D3 / D4 / S1',
                    'duties' => $data['description'],
                    'status' => 'fulfilled',
                ]);
                $vacancy = Vacancy::updateOrCreate(['slug' => Str::slug($data['title'])], [
                    'request_id' => $request->id,
                    'org_unit_id' => $request->org_unit_id,
                    'position_id' => $position->id,
                    'period_id' => $period->id,
                    'program_id' => $program->id,
                    'quota' => $data['quota'],
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'description' => $data['description'],
                    'status' => 'published',
                    'published_at' => $today->copy()->subMinutes($index),
                ]);

                foreach ([
                    'Jurusan' => $data['major'],
                    'Jenjang pendidikan' => 'D3 / D4 / S1',
                    'Durasi' => '3 bulan',
                ] as $label => $description) {
                    VacancyRequirement::updateOrCreate([
                        'vacancy_id' => $vacancy->id,
                        'label' => $label,
                    ], ['description' => $description]);
                }

                foreach ($documents as $document) {
                    VacancyDocumentRequirement::updateOrCreate([
                        'vacancy_id' => $vacancy->id,
                        'document_type_id' => $document->id,
                    ], [
                        'required' => true,
                        'source_allowed' => 'individual',
                    ]);
                }
            }
        });
    }
}
