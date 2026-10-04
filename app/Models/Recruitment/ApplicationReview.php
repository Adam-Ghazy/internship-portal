<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class ApplicationReview extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'recruitment.application_reviews';

    protected $guarded = [];

    protected $casts = [
        'acted_at' => 'datetime',
    ];
}
