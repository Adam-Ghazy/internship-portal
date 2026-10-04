<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $table = 'recruitment.application_documents';

    public $timestamps = false;

    protected $guarded = [];
}
