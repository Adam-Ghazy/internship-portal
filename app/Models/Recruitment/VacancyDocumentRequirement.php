<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyDocumentRequirement extends Model
{
    protected $table = 'recruitment.vacancy_document_requirements';

    protected $primaryKey = ['vacancy_id', 'document_type_id'];

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    // Eloquent's default persistence assumes a single-column primary key.
    protected function setKeysForSelectQuery($query)
    {
        foreach ($this->primaryKey as $key) {
            $query->where($key, $this->original[$key] ?? $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        return $this->setKeysForSelectQuery($query);
    }
}
