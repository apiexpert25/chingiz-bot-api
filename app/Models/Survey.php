<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected   $fillable = [
        'telegram_id',
        'items'
    ];
    protected $guarded = [
        'id',
    ];
}
