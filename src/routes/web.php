<?php

use App\Http\Controllers\HubSpotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/csv', [HubSpotController::class, 'index'])->name('csv.index');
Route::post('/csv', [HubSpotController::class, 'uploadCsv'])->name('upload.csv');
