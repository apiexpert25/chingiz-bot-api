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
        Route::get('/voice/{voice_id}',     'getVoice');
        Route::post('/survey',              'survey');
        Route::get('/statistics',           'statistics');
        Route::get('/find-survey/{telegram_id}', 'findSurvey');
        Route::get('/find-voice/{telegram_id}',  'findVoice');
    });
