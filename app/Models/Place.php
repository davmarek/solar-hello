<?php

namespace App\Models;

use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory, SoftDeletes;

    public const int MAX_INCOMING_ALLOCATIONS = 5;

    protected $fillable = [
        'group_id',
        'user_id',
        'name',
        'description',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): HasOne
    {
        return $this->hasOne(Integration::class);
    }

    public function energyReadings(): HasMany
    {
        return $this->hasMany(EnergyReading::class);
    }

    public function availablePowerSnapshots(): HasMany
    {
        return $this->hasMany(AvailablePowerSnapshot::class);
    }

    public function allocationsOut(): HasMany
    {
        return $this->hasMany(Allocation::class, 'source_place_id');
    }

    public function allocationsIn(): HasMany
    {
        return $this->hasMany(Allocation::class, 'target_place_id');
    }

    // HELPER FUNCTIONS ==========
    public function hasIntegration(): bool
    {
        return $this->integration()->exists();
    }

    public function hasAllocationIn(): bool
    {
        return $this->allocationsIn()->exists();
    }

    public function incomingAllocationsCount(): int
    {
        return $this->allocationsIn()->count();
    }

    public function canReceiveMoreAllocations(): bool
    {
        return $this->incomingAllocationsCount() < self::MAX_INCOMING_ALLOCATIONS;
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(EnergyReading::class)->latestOfMany('recorded_at');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(AvailablePowerSnapshot::class)->latestOfMany('calculated_at');
    }

    /**
     * The sum of all outgoing allocation percentages from this place.
     * Must not exceed 100. Checked before saving a new allocation.
     */
    public function totalAllocatedPercentage(): int
    {
        return (int) $this->allocationsOut()->sum('percentage');
    }

    /**
     * Remaining percentage this place can still allocate to others.
     */
    public function remainingAllocationPercentage(): int
    {
        return 100 - $this->totalAllocatedPercentage();
    }
}
