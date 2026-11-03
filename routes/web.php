<?php

use App\Http\Controllers\OrdenReporteController;
use App\Http\Controllers\OrdenVerificacionController;
use App\Models\Orden;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/ordenes/{orden}/reporte', OrdenReporteController::class)
    ->middleware('auth')
    ->name('ordenes.reporte');

Route::get('/verificar-pedido/{token}', OrdenVerificacionController::class)
    ->middleware('throttle:30,1')
    ->name('ordenes.verificar');

Route::get('/ordenes/{orden}/ver-html', function (Orden $orden) {
    // Retornamos directamente la vista para ver el HTML puro en el navegador
    return view('ordenes.reporte', ['orden' => $orden]);
})->name('ordenes.ver-html')->middleware(['auth']); // Protégela con tu middleware de autenticación

// Route::get('/registro-autorizado', function () {
//     return view('auth.registro-autorizado');
// })->name('registro.autorizado');
