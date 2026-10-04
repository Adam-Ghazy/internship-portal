<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyRequirement extends Model
{
    protected $table = 'recruitment.vacancy_requirements';

    public $timestamps = false;

    protected $guarded = [];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }
}
