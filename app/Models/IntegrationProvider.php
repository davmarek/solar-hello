<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationProvider extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'handler_class',
        'config_schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class, 'provider_id');
    }
}
