<?php

use App\Http\Controllers\LinesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/lines', LinesController::class)
    ->name('lines.index');
