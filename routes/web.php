<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('translations.index'));

Route::get('/registrar', [RegistrationController::class, 'requestForm'])->name('register.request');
Route::post('/registrar', [RegistrationController::class, 'sendToken'])->middleware('throttle:5,1')->name('register.send');
Route::get('/confirmar-email/{token}', [RegistrationController::class, 'completeForm'])->name('register.complete');
Route::post('/confirmar-email/{token}', [RegistrationController::class, 'complete'])->middleware('throttle:5,1')->name('register.complete.store');

Route::middleware('auth')->group(function () {
    Route::get('/traducoes', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/traducoes/{source}', [TranslationController::class, 'store'])->name('translations.store');
});
