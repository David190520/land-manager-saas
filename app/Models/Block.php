<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'code',
        'description',
        'total_lots',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function getAvailableLotsCountAttribute(): int
    {
        return $this->lots()->where('status', 'available')->count();
    }

    public function getReservedLotsCountAttribute(): int
    {
        return $this->lots()->where('status', 'reserved')->count();
    }

    public function getSoldLotsCountAttribute(): int
    {
        return $this->lots()->where('status', 'sold')->count();
    }

    public function getPendingApprovalLotsCountAttribute(): int
    {
        return $this->lots()->where('status', 'pending_approval')->count();
    }
}
