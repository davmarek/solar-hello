<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_place_id',
        'target_place_id',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------

    public function sourcePlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'source_place_id');
    }

    public function targetPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'target_place_id');
    }
}
