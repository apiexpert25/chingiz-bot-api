<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(ApiController::class)
    ->middleware('validate.key')
    ->group(function () {
        Route::post('/voice',               'voice');
        Route::post('/survey',              'survey');
        Route::post('/voice/{voice_id}',     'checkVoiceStatus');
        Route::post('/statistics',           'statistics');
        Route::post('/check-survey/{telegram_id}', 'checkSurvey');
        Route::post('/check-voice/{telegram_id}',  'checkVoice');
    });
