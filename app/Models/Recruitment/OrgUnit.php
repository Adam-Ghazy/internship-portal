<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgUnit extends Model
{
    protected $table = 'recruitment.org_units';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
