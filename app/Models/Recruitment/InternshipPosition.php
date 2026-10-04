<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class InternshipPosition extends Model
{
    protected $table = 'recruitment.internship_positions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];
}
