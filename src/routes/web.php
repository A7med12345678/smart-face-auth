<?php

use Illuminate\Support\Facades\Route;
use Vega\FaceLogin\Http\Controllers\FaceLoginController;

Route::group(['middleware' => ['web']], function () {
    // Guest Routes
    Route::group(['prefix' => 'face-auth'], function () {
        Route::get('/login', [FaceLoginController::class, 'showLogin'])->name('face.login.form');
        Route::post('/login', [FaceLoginController::class, 'login'])->name('face.login.submit');

        Route::get('/register', [FaceLoginController::class, 'showRegister'])->name('face.register.form');
        Route::post('/register', [FaceLoginController::class, 'store'])->name('face.register.store');
    });

    // Auth Routes
    Route::group(['middleware' => ['auth'], 'prefix' => 'face-auth'], function () {
        Route::post('/verify-session', [FaceLoginController::class, 'verifySession'])->name('face.verify.session');
        Route::post('/report-violation', [FaceLoginController::class, 'reportViolation'])->name('face.report.violation');
    });
});
