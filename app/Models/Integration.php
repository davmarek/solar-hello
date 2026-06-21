<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'provider_id',
        'label',
        'credentials',
        'settings',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            // Automatically AES-encrypts/decrypts using APP_KEY — never stored in plaintext
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    // Never include credentials in JSON serialization (e.g. API responses)
    protected $hidden = ['credentials'];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(EnergyReading::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function latestReading(): HasOne
    {
        return $this->hasOne(EnergyReading::class)->latestOfMany('recorded_at');
    }
}
