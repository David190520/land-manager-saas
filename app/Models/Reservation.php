<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'lot_id',
        'client_id',
        'user_id',
        'seller_id',
        'down_payment',
        'payment_deadline',
        'status',
        'notes',
        'confirmed_at',
        'expired_at',
        'payment_proof',
    ];

    protected $casts = [
        'down_payment' => 'decimal:2',
        'payment_deadline' => 'date',
        'confirmed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function paymentPlan(): HasOne
    {
        return $this->hasOne(PaymentPlan::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'active'
            && now()->greaterThan($this->payment_deadline)
            && !$this->paymentPlan?->payments()->where('status', 'paid')->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_approval' => 'Pendiente Aprobación',
            'active' => 'Activa',
            'confirmed' => 'Confirmada',
            'expired' => 'Expirada',
            'cancelled' => 'Cancelada',
            default => 'Desconocido',
        };
    }
}
