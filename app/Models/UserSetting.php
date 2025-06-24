<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'date_format',
        'timezone',
        'email_notifications',
        'daily_reminder',
        'reminder_time',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'daily_reminder' => 'boolean',
        'reminder_time' => 'datetime:H:i:s',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods
    public static function getDefaultSettings(): array
    {
        return [
            'currency' => 'IDR',
            'date_format' => 'Y-m-d',
            'timezone' => 'Asia/Jakarta',
            'email_notifications' => true,
            'daily_reminder' => false,
            'reminder_time' => '09:00:00',
        ];
    }
}
