<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class ApplicationSubmission extends Model
{
    protected $table = 'recruitment.application_submissions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'profile_snapshot' => 'array',
        'education_snapshot' => 'array',
        'vacancy_snapshot' => 'array',
        'submitted_at' => 'datetime',
    ];
}
