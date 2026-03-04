<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    protected $fillable = [
        'reservation_id',
        'total_price',
        'down_payment',
        'financed_amount',
        'total_installments',
        'installment_amount',
        'interest_rate',
        'start_date',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'financed_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) ($this->financed_amount - $this->total_paid);
    }

    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->payments()->where('status', 'paid')->count();
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->financed_amount <= 0)
            return 100;
        return round(($this->total_paid / $this->financed_amount) * 100, 1);
    }
}
