<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceMessages extends Model
{
    protected   $fillable = [
        'telegram_id',
        'voice_id',
        'telegram_id',
        'status',
        'download_url',
    ];
    protected $guarded = [
        'id',
    ];
}
