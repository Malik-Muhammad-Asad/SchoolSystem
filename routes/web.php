<?php

use App\Http\Controllers\MarkSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to Filament's login page
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin');
})->name('login');


Route::get('/mark-sheets/{student}/{term}', [MarkSheetController::class, 'downloadSingle'])
    ->name('mark-sheets.download-single');