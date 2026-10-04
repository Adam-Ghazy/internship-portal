<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class InternshipRequest extends Model
{
    protected $table = 'recruitment.internship_requests';

    public $timestamps = false;

    protected $guarded = [];
}
