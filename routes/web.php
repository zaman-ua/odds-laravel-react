<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/lines', fn () => view('lines.index'))
    ->name('lines.index');
