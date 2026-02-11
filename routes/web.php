<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::controller(ApiController::class)
    ->prefix('/api')->group(function () {;
//        Route::middleware('auth')->group(function () {
        Route::post('/voice',               'voice');
        Route::get('/voice/{voice_id}',    'getVoice');
        Route::post('/survey',              'survey');

    });
