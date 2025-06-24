<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->orWhere('is_default', true);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_default && !$this->transactions()->exists();
    }
}
