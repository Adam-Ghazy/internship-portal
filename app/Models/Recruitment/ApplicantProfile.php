<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfile extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'recruitment.applicant_profiles';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(RecruitmentUser::class, 'user_id');
    }
}
