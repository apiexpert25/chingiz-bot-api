<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VoiceMessages extends Model
{
    protected $fillable = [
        'telegram_id',
        'voice_id',
        'status',
        'voice_download_link',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voiceMessage) {
            if (!$voiceMessage->voice_id) {
                $voiceMessage->voice_id = (string) Str::uuid();
            }
        });
    }

    public function getVoiceStatusLinkAttribute()
    {
        return url("/api/voice/{$this->voice_id}");
    }
}
