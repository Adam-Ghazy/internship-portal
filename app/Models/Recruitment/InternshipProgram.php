<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class InternshipProgram extends Model
{
    protected $table = 'recruitment.internship_programs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];
}
