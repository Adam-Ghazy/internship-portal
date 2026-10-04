<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'recruitment.document_types';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];
}
