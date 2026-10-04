<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    protected $table = 'recruitment.applications';

    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function applicantProfile(): BelongsTo
    {
        return $this->belongsTo(ApplicantProfile::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentUser::class, 'applicant_user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ApplicationReview::class);
    }

    public function currentSubmission(): HasOne
    {
        return $this->hasOne(ApplicationSubmission::class, 'id', 'current_submission_id');
    }
}
