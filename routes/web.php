<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\SampleSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Internal review page; answers 404 outside the local and testing environments.
Route::get('/musterbogen', SampleSheetController::class)->name('sample-sheet');
