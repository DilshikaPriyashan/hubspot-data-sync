<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HubSpotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', [HubSpotController::class, 'index'])->name('csv.index');
Route::post('/contact', [HubSpotController::class, 'uploadCsv'])->name('upload.csv');

Route::get('/company', [CompanyController::class, 'index'])->name('company.index');
Route::post('/company', [CompanyController::class, 'create'])->name('company.create');
