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
        'error_message',
    ];

}
