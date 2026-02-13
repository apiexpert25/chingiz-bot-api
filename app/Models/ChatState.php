<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int chat_id,
 * @property string status,
 * @property string last_message,
 */


class ChatState extends Model
{
    protected $fillable =[
        'chat_id',
        'status',
        'last_message',
    ];
}
