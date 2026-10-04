<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $table = 'recruitment.institutions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];
}
