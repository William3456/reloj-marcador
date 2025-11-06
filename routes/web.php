<?php

use App\Http\Controllers\Empresa\EmpresaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home'); //->name('home') = para asignar nombre a la ruta 


Route::get('/dashboard', function () {
    return view('dashboard');
    //Route::get('/empresa/show', [EmpresaController::class, 'show'])->name('empresas.show')->middleware('check.role:1');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('/empresas', 'empresas.lista')->name('empresas.home')->middleware('check.role:2'); //Para listado, por ahorita no
    Route::post('/empresa', [EmpresaController::class, 'store'])->name('empresas.store')->middleware('check.role:1');
    Route::get('/empresa/show', [EmpresaController::class, 'show'])->name('empresas.show')->middleware('check.role:1');
});

require __DIR__.'/auth.php';
