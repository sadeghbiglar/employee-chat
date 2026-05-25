<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasUlids, SoftDeletes; // اضافه شدن HasUlids و SoftDeletes

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'type',
    ];

    // رابطه: این پیام متعلق به کدام چت است
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // رابطه: چه کسی این پیام را ارسال کرده است
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}