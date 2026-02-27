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
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\Turnos\Turnos;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
})->middleware(['auth', 'verified','prevent-back-history'])->name('home');

/*
|--------------------------------------------------------------------------
| RUTAS PARA EMPLEADOS (ROL 3) - LA APP MÓVIL
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'check.role:3','prevent-back-history'])->group(function () {

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
Route::middleware(['auth', 'verified', 'check.role:1-2','prevent-back-history'])->group(function () {

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
Route::middleware(['auth', 'verified', 'check.role:1','prevent-back-history'])->group(function () {
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
Route::middleware(['auth','prevent-back-history'])->group(function () {
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
Route::get('/limpiar-todo', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return 'Limpio';
});
Route::get('/limpiar-cache', function() {
    Artisan::call('optimize:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    return 'Caché de Laravel limpia y optimizada al 100%';
});

Route::get('/limpiar-historiales', function () {
    // ¡IMPORTANTE! HAZ UN RESPALDO DE TU BASE DE DATOS ANTES DE EJECUTAR ESTO
    DB::beginTransaction();

    try {
        // 1. Borrar asignaciones viejas/cerradas
        DB::table('horarios_trabajadores')->where('es_actual', 0)->delete();
        DB::table('horarios_sucursales')->where('es_actual', 0)->delete();

        // 2. Vaciar históricos viejos
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        HorarioHistorico::query()->delete(); 
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $horarios = horario::all();

        foreach ($horarios as $horario) {
            // 3. Crear el histórico fundacional perfecto (desde el 2020)
            $historico = HorarioHistorico::create([
                'id_horario'    => $horario->id,
                'tipo_horario'  => $horario->permitido_marcacion,
                'hora_entrada'  => $horario->hora_ini,
                'hora_salida'   => $horario->hora_fin,
                'tolerancia'    => $horario->tolerancia,
                'dias'          => $horario->dias,
                'vigente_desde' => '2020-01-01 00:00:00', 
                'vigente_hasta' => null,
            ]);

            // 4. Conectar a los empleados/sucursales activos con este nuevo histórico
            if ($horario->permitido_marcacion == 1) { 
                DB::table('horarios_sucursales')
                    ->where('id_horario', $horario->id)
                    ->update([
                        'id_horario_historico' => $historico->id,
                        'es_actual' => 1,
                        'fecha_fin' => null
                    ]);
            } else { 
                DB::table('horarios_trabajadores')
                    ->where('id_horario', $horario->id)
                    ->update([
                        'id_horario_historico' => $historico->id,
                        'es_actual' => 1,
                        'fecha_fin' => null
                    ]);
            }

            // 5. NUEVO: Rescatar las marcaciones antiguas
            // Basado en tu código anterior, asumo que la columna se llama id_horario_historico_empleado
            DB::table('marcaciones_empleados') // O el nombre exacto de tu tabla de marcaciones
                ->where('id_horario', $horario->id)
                ->update([
                    'id_horario_historico_empleado' => $historico->id 
                    // Si tu columna se llama distinto (ej. id_horario_historico), cámbialo aquí arriba
                ]);
        }

        DB::commit();
        return "¡Éxito total! Historiales purgados, asignaciones arregladas y marcaciones pasadas enlazadas correctamente.";

    } catch (\Exception $e) {
        DB::rollBack();
        return "Error al limpiar: " . $e->getMessage();
    }
});

Route::get('/sumar-hora-global', function () {
    // 1. Obtenemos todas las tablas de la base de datos actual
    $tablas = DB::select('SHOW TABLES');
    $tablasAfectadas = [];

    foreach ($tablas as $tabla) {
        // Extraemos el nombre exacto de la tabla
        $nombreTabla = array_values((array)$tabla)[0];

        // 2. Verificamos si la tabla tiene los campos de Laravel antes de intentar actualizarla
        // Esto evita errores en tablas como 'migrations' o tablas pivote que no llevan timestamps
        if (Schema::hasColumn($nombreTabla, 'created_at') && Schema::hasColumn($nombreTabla, 'updated_at')) {
            
            // 3. Ejecutamos la suma de 1 hora directamente en la base de datos
            DB::table($nombreTabla)->update([
                'created_at' => DB::raw('DATE_ADD(created_at, INTERVAL 1 HOUR)'),
                'updated_at' => DB::raw('DATE_ADD(updated_at, INTERVAL 1 HOUR)')
            ]);

            $tablasAfectadas[] = $nombreTabla;
        }
    }

    return "¡Éxito! Se sumó 1 hora a los timestamps de las siguientes tablas: <br><br>" . implode('<br>', $tablasAfectadas);
});
require __DIR__.'/auth.php';
