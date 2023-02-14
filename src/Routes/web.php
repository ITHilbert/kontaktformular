<?php

use ITHilbert\Kontaktformular\Http\Controllers\KontaktformularController;
use Illuminate\Support\Facades\Route;

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

//##############################################
//# Mail / Anfrage
//##############################################

//Anfrage Kontaktformular Senden
Route::any('anfrage', [KontaktformularController::class, 'anfrage'])->middleware('web')->name('anfrage');
Route::get('danke-formular', [KontaktformularController::class, 'danke_formular'])->name('danke_formular');


