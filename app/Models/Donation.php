<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'donor_name',
        'message',
        'is_anonymous',
        'show_in_list',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'show_in_list' => 'boolean',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('show_in_list', true)
                    ->whereHas('payment', function ($q) {
                        $q->where('status', 'success');
                    });
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Anonymous' : ($this->donor_name ?? 'Anonymous');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->payment->amount, 0, ',', '.');
    }
}
