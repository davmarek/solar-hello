<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = ['email', 'token', 'group_id'];

    /**
     * @return BelongsTo<Group,Invitation>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
