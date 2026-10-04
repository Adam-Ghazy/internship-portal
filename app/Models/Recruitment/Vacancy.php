<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    protected $table = 'recruitment.vacancies';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'published_at' => 'datetime',
        'quota' => 'integer',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(InternshipPosition::class, 'position_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPeriod::class, 'period_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(InternshipProgram::class, 'program_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VacancyRequirement::class)->orderBy('id');
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(VacancyDocumentRequirement::class)->orderBy('document_type_id');
    }

    public function isClosed(): bool
    {
        return $this->period->closes_at->isPast();
    }
}
