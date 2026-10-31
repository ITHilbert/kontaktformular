<?php

use Illuminate\Support\Facades\Route;
use ITHilbert\Kontaktformular\Controllers\KontaktformularController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ##############################################
// # Mail / Anfrage
// ##############################################

// Anfrage Kontaktformular Senden
Route::middleware('web')->group(function () {
    Route::post('anfrage', [KontaktformularController::class, 'anfrage'])->name('anfrage')->middleware('throttle:6,1');
    Route::get('kontaktformular/csrf-token', [KontaktformularController::class, 'csrf_token'])->name('kontaktformular.csrf_token')->middleware('throttle:60,1');
    Route::get('danke-formular', [KontaktformularController::class, 'danke_formular'])->name('danke_formular');
    Route::get('danke-bot-formular', [KontaktformularController::class, 'danke_bot_formular'])->name('danke_bot_formular');
    Route::get('kontaktformular/file/{hash}/{name}/{id}', [KontaktformularController::class, 'file_download'])->name('kontaktformular.file');
});
