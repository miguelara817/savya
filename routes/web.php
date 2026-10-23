<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Route::get('/registro-autorizado', function () {
//     return view('auth.registro-autorizado');
// })->name('registro.autorizado');
