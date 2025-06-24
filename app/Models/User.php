<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    // Accessors & Mutators
    public function getIsPremiumAttribute(): bool
    {
        return $this->role === 'premium' || $this->role === 'admin';
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    // Scopes
    public function scopePremium($query)
    {
        return $query->whereIn('role', ['premium', 'admin']);
    }

    public function scopeFree($query)
    {
        return $query->where('role', 'free');
    }

    // Helper Methods
    public function canCreateCategory(): bool
    {
        if ($this->is_premium) {
            return true;
        }
        
        // Free users can create max 5 custom categories
        return $this->categories()->count() < 5;
    }

    public function canExportData(): bool
    {
        return $this->is_premium;
    }
}