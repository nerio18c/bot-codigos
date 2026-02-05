<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CodigoController;

Route::get('/', function () {
    return view('consulta');
});

Route::post('/consultar', [CodigoController::class, 'buscar']);