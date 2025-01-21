<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to Filament's login page
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin');
})->name('login');

