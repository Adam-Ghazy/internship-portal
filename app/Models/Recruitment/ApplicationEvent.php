<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ApplicationEvent extends Model
{
    protected $table = 'recruitment.application_events';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function save(array $options = [])
    {
        throw new LogicException('Riwayat lamaran hanya dapat ditulis oleh fungsi database.');
    }

    public function delete()
    {
        throw new LogicException('Riwayat lamaran tidak dapat dihapus.');
    }
}
