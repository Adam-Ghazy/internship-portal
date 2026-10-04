<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class RecruitmentPeriod extends Model
{
    protected $table = 'recruitment.recruitment_periods';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];
}
