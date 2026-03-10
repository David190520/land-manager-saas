<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'location',
        'municipality',
        'department',
        'total_area',
        'area_unit',
        'price_per_m2',
        'status',
        'image_path',
    ];

    protected $casts = [
        'total_area' => 'decimal:2',
        'price_per_m2' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }

    public function lots(): HasManyThrough
    {
        return $this->hasManyThrough(Lot::class, Block::class);
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

    public function getTotalLotsCountAttribute(): int
    {
        return $this->lots()->count();
    }
}
