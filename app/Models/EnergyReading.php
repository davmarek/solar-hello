<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyReading extends Model
{
    protected $fillable = [
        'integration_id',
        'place_id', // Denormalized
        'group_id', // Denormalized
        'recorded_at',
        'inverter_power_w',
        'grid_power_w',
        'battery_power_w',
        'battery_soc_pct',
        'energy_today_kwh',
        'energy_total_kwh',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'inverter_power_w' => 'float',
            'grid_power_w' => 'float',
            'battery_power_w' => 'float',
            'battery_soc_pct' => 'float',
            'energy_today_kwh' => 'float',
            'energy_total_kwh' => 'float',
            'raw_payload' => 'array',
        ];
    }
    // -------------------------------------------------------------------------

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
