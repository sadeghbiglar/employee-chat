<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, HasUlids; // استفاده از HasUlids اجباری است

    protected $fillable = [
        'is_group',
        'name',
    ];

    protected $casts = [
        'is_group' => 'boolean',
    ];

    // رابطه: کاربرانی که در این چت عضو هستند
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    // رابطه: پیام‌های ارسال شده در این چت
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}