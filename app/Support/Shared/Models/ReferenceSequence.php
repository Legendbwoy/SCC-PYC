<?php

declare(strict_types=1);

namespace App\Support\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceSequence extends Model
{
    protected $fillable = [
        'sequence_type',
        'year',
        'last_value',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_value' => 'integer',
        ];
    }
}
