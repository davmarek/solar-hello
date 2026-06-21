<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailablePowerSnapshot extends Model
{
    protected $fillable = [
        'place_id',
        'group_id', // Denormalized
        'calculated_at',
        'allocated_w',
        'consumption_w',
        'net_w',
        'is_estimate',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
            'allocated_w' => 'float',
            'consumption_w' => 'float',
            'net_w' => 'float',
            'is_estimate' => 'boolean',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Whether the place has more allocated power than it is currently consuming.
     * Null if consumption is unknown (is_estimate = true).
     */
    public function hasSurplus(): ?bool
    {
        if ($this->net_w === null) {
            return null;
        }

        return $this->net_w > 0;
    }
}
