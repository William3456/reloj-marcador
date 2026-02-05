<?php

use App\Http\Controllers\Api\DeptosPuestosController;
use App\Http\Controllers\Departamentos\DepartamentosController;
use App\Http\Controllers\Empleados\EmpleadoController; // Para el test de correo
// Importación de Controladores
use App\Http\Controllers\Empresa\EmpresaController;
use App\Http\Controllers\Horarios\HorarioController;
use App\Http\Controllers\HorariosEmpleados\HorarioEmpleadoController;
use App\Http\Controllers\HorariosSucursal\HorarioSucursalController;
use App\Http\Controllers\MarcacionApp\HistorialController;
use App\Http\Controllers\MarcacionApp\MarcacionController;
use App\Http\Controllers\Permiso\PermisoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Puestos\PuestosController;
use App\Http\Controllers\Reportes\ReporteEmpleadoController;
use App\Http\Controllers\Reportes\ReporteMarcacionesController;
use App\Http\Controllers\Sucursales\SucursalController; // Para la API interna
use App\Models\Turnos\Turnos;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS Y DE REDIRECCIÓN INICIAL
|--------------------------------------------------------------------------
*/

// Ruta Raíz Inteligente: Decide qué mostrar según el rol
Route::get('/', function () {
    $user = Auth::user();

    // Si es Empleado (Rol 3), ir directo a marcación
    if ($user->id_rol == 3) {
        return redirect()->route('marcacion.inicio');
    }

    // Si es Admin o Gerente (Rol 1 o 2), mostrar Dashboard
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('home');

/*
|--------------------------------------------------------------------------
| RUTAS PARA EMPLEADOS (ROL 3) - LA APP MÓVIL
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'check.role:3'])->group(function () {

    Route::controller(MarcacionController::class)->prefix('marcacion')->name('marcacion.')->group(function () {
        Route::get('/inicio', 'index')->name('inicio');
        Route::post('/store', 'store')->name('store');
        Route::get('/historial', [HistorialController::class, 'index'])->name('historial');
    });

});

/*
|--------------------------------------------------------------------------
| RUTAS ADMINISTRATIVAS (ROLES 1 y 2) - GESTIÓN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'check.role:1-2'])->group(function () {

    // Dashboard Administrativo
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // --- SUCURSALES ---
    Route::controller(SucursalController::class)->prefix('sucursales')->name('sucursales.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create')->middleware('check.role:1');
        Route::post('/create', 'store')->name('store')->middleware('check.role:1');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
        Route::get('/{id}/info', [SucursalController::class, 'showInfo'])->name('sucursales.info');
    });

    // --- HORARIOS ---
    Route::controller(HorarioController::class)->prefix('horarios')->name('horarios.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
    });

    // --- EMPLEADOS ---
    Route::controller(EmpleadoController::class)->prefix('empleados')->name('empleados.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
        Route::get('/{id}/info', 'show')->name('info');
    });

    // --- PERMISOS ---
    Route::controller(PermisoController::class)->prefix('permisos')->name('permisos.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
    });

    // --- DEPARTAMENTOS ---
    Route::controller(DepartamentosController::class)->prefix('departamentos')->name('departamentos.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
    });

    // --- PUESTOS ---
    Route::controller(PuestosController::class)->prefix('puestos')->name('puestos.')->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/create', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
    });

    // --- ASIGNACIÓN DE HORARIOS ---
    Route::controller(HorarioEmpleadoController::class)->group(function () {
        Route::get('/horario-trabajador', 'index')->name('empleadoshorarios.asign');
        Route::post('/horario-trabajador/store', 'store')->name('horario_trabajador.store');
    });

    Route::controller(HorarioSucursalController::class)->group(function () {
        Route::get('/horario-sucursal', 'index')->name('sucursaleshorarios.asign');
        Route::post('/horario-sucursal/store', 'store')->name('horario_sucursal.store');
    });

    Route::controller(MarcacionController::class)->prefix('marcaciones')->name('marcaciones.')->group(function () {
        Route::get('/index', 'indexPanel')->name('index');
    });

    // --- REPORTES ---
    Route::controller(ReporteEmpleadoController::class)->prefix('reportes/empleados')->name('reportes.empleados.')->group(function () {
        Route::get('/rep-empleados', 'porSucursal')->name('empleados_rep');
    });
    Route::controller(ReporteMarcacionesController::class)->prefix('reportes/marcaciones')->name('reportes.marcaciones.')->group(function () {
        Route::get('/rep-marcaciones', 'index')->name('marcaciones_rep');
    });

    // Generación de PDF (Se deja fuera del grupo 'prefix' anterior para mantener tu nombre de ruta exacto si lo usas en JS)
    Route::get('/reportes/empleados/pdf', [ReporteEmpleadoController::class, 'generarPdf'])->name('empleados.pdf');
    Route::get('/reportes/marcaciones/pdf', [ReporteMarcacionesController::class, 'generarPdf'])->name('marcaciones.pdf');
    // Rutas de Reporte de Marcaciones
    //Route::get('/reportes/marcaciones', [ReporteMarcacionesController::class, 'index'])->name('marcaciones.index');
    //Route::get('/reportes/marcaciones/pdf', [ReporteMarcacionesController::class, 'generarPdf'])->name('marcaciones.pdf');

    // --- EMPRESAS (LISTADO SOLO PARA ROL 2 SEGÚN TU CÓDIGO ANTERIOR) ---
    Route::view('/empresas', 'empresas.lista')->name('empresas.home')->middleware('check.role:2');

});

/*
|--------------------------------------------------------------------------
| RUTAS SUPER ADMIN (SOLO ROL 1)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'check.role:1'])->group(function () {
    Route::controller(EmpresaController::class)->prefix('empresa')->name('empresas.')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/show', 'show')->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| PERFIL DE USUARIO (COMÚN PARA TODOS)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| RUTAS API INTERNAS (Consumidas por AJAX/JS en el mismo dominio)
|--------------------------------------------------------------------------
*/
Route::middleware(['api'])->prefix('api')->group(function () {
    Route::get('/turnos', function () {
        return Turnos::all();
    });
    Route::get('/puestosDptosBySucId/{sucursalId}', [DeptosPuestosController::class, 'puestosAndDeptos']);
    Route::get('/sucursal/details/{id}', [HorarioEmpleadoController::class, 'getSucursalDetails']);
    Route::get('/empleados/sucursal/{id}', [HorarioEmpleadoController::class, 'getEmpleadosBySucursal']);
    Route::get('/horarios-sucursal/{id}', [HorarioSucursalController::class, 'getBySucursal']);
});

/*
|--------------------------------------------------------------------------
| UTILIDADES / PRUEBAS
|--------------------------------------------------------------------------
*/
Route::get('/test-mail', function () {
    Mail::raw('Correo funcionando!', function ($m) {
        $m->to('tu_correo@gmail.com')->subject('Prueba');
    });
});
Route::get('/crear-enlace', function () {
    try {
        Artisan::call('storage:link');
        return "¡Enlace simbólico creado exitosamente!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
require __DIR__.'/auth.php';
