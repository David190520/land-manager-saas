<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lot extends Model
{
    protected $fillable = [
        'block_id',
        'lot_number',
        'area',
        'price',
        'front_length',
        'depth_length',
        'status',
        'notes',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'price' => 'decimal:2',
        'front_length' => 'decimal:2',
        'depth_length' => 'decimal:2',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservation(): HasOne
    {
        return $this->hasOne(Reservation::class)->whereIn('status', ['active', 'confirmed'])->latest();
    }

    public function getFullIdentifierAttribute(): string
    {
        return $this->block->name . ' - Lote ' . $this->lot_number;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'green',
            'pending_approval' => 'yellow',
            'reserved' => 'orange',
            'sold' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Disponible',
            'pending_approval' => 'Pendiente Aprob.',
            'reserved' => 'Reservado',
            'sold' => 'Vendido',
            default => 'Desconocido',
        };
    }
}
