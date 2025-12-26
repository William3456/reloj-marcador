<?php

use App\Http\Controllers\Api\DeptosPuestosController;
use App\Http\Controllers\Departamentos\DepartamentosController;
use App\Http\Controllers\Empleados\EmpleadoController;
use App\Http\Controllers\Empresa\EmpresaController;
use App\Http\Controllers\Horarios\HorarioController;
use App\Http\Controllers\HorariosEmpleados\HorarioEmpleadoController;
use App\Http\Controllers\Permiso\PermisoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sucursales\SucursalController;
use App\Models\Turnos\Turnos;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
    // Route::get('/empresa/show', [EmpresaController::class, 'show'])->name('empresas.show')->middleware('check.role:1');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
    // Route::get('/empresa/show', [EmpresaController::class, 'show'])->name('empresas.show')->middleware('check.role:1');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('/empresas', 'empresas.lista')->name('empresas.home')->middleware('check.role:2'); // Para listado, por ahorita no
    Route::post('/empresa', [EmpresaController::class, 'store'])->name('empresas.store')->middleware('check.role:1');
    Route::get('/empresa/show', [EmpresaController::class, 'show'])->name('empresas.show')->middleware('check.role:1');

    // Para sucurusales
    Route::get('sucursales/create', [SucursalController::class, 'create'])
        ->name('sucursales.create')
        ->middleware('check.role:1-2');
    Route::post('sucursales/create', [SucursalController::class, 'store'])
        ->name('sucursales.store')
        ->middleware('check.role:1-2');
    Route::get('sucursales/index', [SucursalController::class, 'index'])
        ->name('sucursales.index')
        ->middleware(['check.role:1-2']);
    Route::get('sucursales/edit/{id}', [SucursalController::class, 'edit'])
        ->name('sucursales.edit')
        ->middleware('check.role:1-2');
    Route::put('sucursales/update/{id}', [SucursalController::class, 'update'])
        ->name('sucursales.update')
        ->middleware('check.role:1-2');
    Route::delete('sucursales/delete/{id}', [SucursalController::class, 'destroy'])
        ->name('sucursales.delete')
        ->middleware('check.role:1-2');

    // Para horarios
    Route::get('horarios/create', [HorarioController::class, 'create'])
        ->name('horarios.create')
        ->middleware('check.role:1-2');
    Route::post('horarios/create', [HorarioController::class, 'store'])
        ->name('horarios.store')
        ->middleware('check.role:1-2');
    Route::get('horarios/index', [HorarioController::class, 'index'])
        ->name('horarios.index')
        ->middleware(['check.role:1-2']);
    Route::get('horarios/edit/{id}', [HorarioController::class, 'edit'])
        ->name('horarios.edit')
        ->middleware('check.role:1-2');
    Route::put('horarios/update/{id}', [HorarioController::class, 'update'])
        ->name('horarios.update')
        ->middleware('check.role:1-2');
    Route::delete('horarios/delete/{id}', [HorarioController::class, 'destroy'])
        ->name('horarios.delete')
        ->middleware('check.role:1-2');

    // Para Empleados
    Route::get('empleados/create', [EmpleadoController::class, 'create'])
        ->name('empleados.create')
        ->middleware('check.role:1-2');
    Route::post('empleados/create', [EmpleadoController::class, 'store'])
        ->name('empleados.store')
        ->middleware('check.role:1-2');
    Route::get('empleados/index', [EmpleadoController::class, 'index'])
        ->name('empleados.index')
        ->middleware(['check.role:1-2']);
    Route::get('empleados/edit/{id}', [EmpleadoController::class, 'edit'])
        ->name('empleados.edit')
        ->middleware('check.role:1-2');
    Route::put('empleados/update/{id}', [EmpleadoController::class, 'update'])
        ->name('empleados.update')
        ->middleware('check.role:1-2');
    Route::delete('empleados/delete/{id}', [EmpleadoController::class, 'destroy'])
        ->name('empleados.delete')
        ->middleware('check.role:1-2');
    Route::get('/empleados/{id}/info', [EmpleadoController::class, 'show'])->name('empleados.info');

        // Para permisos de Empleados
    Route::get('permisos/create', [PermisoController::class, 'create'])
        ->name('permisos.create')
        ->middleware('check.role:1-2');
    Route::post('permisos/create', [PermisoController::class, 'store'])
        ->name('permisos.store')
        ->middleware('check.role:1-2');
    Route::get('permisos/index', [PermisoController::class, 'index'])
        ->name('permisos.index')
        ->middleware(['check.role:1-2']);
    Route::get('permisos/edit/{id}', [PermisoController::class, 'edit'])
        ->name('permisos.edit')
        ->middleware('check.role:1-2');
    Route::put('permisos/update/{id}', [PermisoController::class, 'update'])
        ->name('permisos.update')
        ->middleware('check.role:1-2');
    Route::delete('permisos/delete/{id}', [PermisoController::class, 'destroy'])
        ->name('permisos.delete')
        ->middleware('check.role:1-2');
    

    // Asignaciones de horarios

    // Guardar asignación de horario
    Route::post('/horario-trabajador/store', [HorarioEmpleadoController::class, 'store'])
        ->name('horario_trabajador.store')->middleware('check.role:1-2');

    Route::get('/horario-trabajador', [HorarioEmpleadoController::class, 'index'])
        ->name('empleadoshorarios.asign');


        // Para departamentos
    Route::get('departamentos/create', [DepartamentosController::class, 'create'])
        ->name('departamentos.create')
        ->middleware('check.role:1-2');
    Route::post('departamentos/create', [DepartamentosController::class, 'store'])
        ->name('departamentos.store')
        ->middleware('check.role:1-2');
    Route::get('departamentos/index', [DepartamentosController::class, 'index'])
        ->name('departamentos.index')
        ->middleware(['check.role:1-2']);
    Route::get('departamentos/edit/{id}', [DepartamentosController::class, 'edit'])
        ->name('departamentos.edit')
        ->middleware('check.role:1-2');
    Route::put('departamentos/update/{id}', [DepartamentosController::class, 'update'])
        ->name('departamentos.update')
        ->middleware('check.role:1-2');
    Route::delete('departamentos/delete/{id}', [DepartamentosController::class, 'destroy'])
        ->name('departamentos.delete')
        ->middleware('check.role:1-2');

});

Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/turnos', function () {
        return Turnos::all();
    });
    Route::get('/puestosDptosBySucId/{sucursalId}', [DeptosPuestosController::class, 'puestosAndDeptos']);
    // Detalles de sucursal
    Route::get('/sucursal/details/{id}', [HorarioEmpleadoController::class, 'getSucursalDetails']);

    // Detalles de empleados por sucursa
    Route::get('/empleados/sucursal/{id}', [HorarioEmpleadoController::class, 'getEmpleadosBySucursal']);
});

Route::get('/test-mail', function () {
    \Illuminate\Support\Facades\Mail::raw('Correo funcionando!', function ($m) {
        $m->to('tu_correo@gmail.com')->subject('Prueba');
    });
});
require __DIR__.'/auth.php';
