<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('prompts.edit');
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/prompts', [PromptController::class, 'edit'])->name('prompts.edit');
    Route::post('/prompts', [PromptController::class, 'update'])->name('prompts.update');
});
