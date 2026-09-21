<?php

use App\Http\Controllers\AdminTranslationController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\TranslationReviewController;
use App\Http\Middleware\RequirePortalAdmin;
use App\Http\Middleware\RequirePortalReviewer;
use App\Http\Middleware\SetPortalLocale;
use Illuminate\Support\Facades\Route;

Route::middleware([SetPortalLocale::class])->group(function () {
    Route::get('/', fn () => auth()->check() ? redirect()->route('translations.index') : redirect()->route('login'));

    Route::middleware('guest')->group(function () {
        Route::get('/entrar',[AuthController::class,'loginForm'])->name('login');
        Route::post('/entrar',[AuthController::class,'login'])->middleware('throttle:5,1')->name('login.store');
        Route::get('/esqueci-senha',[PasswordResetController::class,'requestForm'])->name('password.request');
        Route::post('/esqueci-senha',[PasswordResetController::class,'sendLink'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/redefinir-senha/{token}',[PasswordResetController::class,'resetForm'])->name('password.reset');
        Route::post('/redefinir-senha/{token}',[PasswordResetController::class,'reset'])->middleware('throttle:5,1')->name('password.update');
        Route::get('/registrar',[RegistrationController::class,'requestForm'])->name('register.request');
        Route::post('/registrar',[RegistrationController::class,'sendToken'])->middleware('throttle:5,1')->name('register.send');
        Route::get('/confirmar-email/{token}',[RegistrationController::class,'completeForm'])->name('register.complete');
        Route::post('/confirmar-email/{token}',[RegistrationController::class,'complete'])->middleware('throttle:5,1')->name('register.complete.store');
    });

    Route::post('/sair',[AuthController::class,'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/traducoes',[TranslationController::class,'index'])->name('translations.index');
        Route::post('/traducoes/{source}',[TranslationController::class,'store'])->name('translations.store');
        Route::get('/traducoes-exportar',[TranslationController::class,'export'])->name('translations.export');

        Route::prefix('revisao')->middleware(RequirePortalReviewer::class)->group(function () {
            Route::get('/traducoes',[TranslationReviewController::class,'index'])->name('review.translations.index');
            Route::post('/traducoes/{translation}',[TranslationReviewController::class,'update'])->name('review.translations.update');
        });

        Route::prefix('admin')->middleware(RequirePortalAdmin::class)->group(function () {
            Route::get('/traducoes',[AdminTranslationController::class,'index'])->name('admin.translations.index');
            Route::post('/traducoes/sincronizar',[AdminTranslationController::class,'sync'])->name('admin.translations.sync');
            Route::post('/traducoes/publicar',[AdminTranslationController::class,'publish'])->name('admin.translations.publish');
            Route::get('/auditoria',[AuditController::class,'index'])->name('admin.audit.index');
        });
    });
});
