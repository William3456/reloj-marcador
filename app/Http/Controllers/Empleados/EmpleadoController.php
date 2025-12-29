<?php

namespace App\Http\Controllers\Empleados;

use App\Http\Controllers\Controller;
use App\Mail\EmpleadoPasswordMail;
use App\Models\Departamento\Departamento;
use App\Models\Empleado\Empleado;
use App\Models\Empresa\Empresa;
use App\Models\Puesto\Puesto;
use App\Models\Sucursales\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        $empleados = Empleado::visiblePara($user)
        ->with(['puesto', 'departamento', 'sucursal', 'empresa',  'user.rol'])
            ->orderBy('id', 'desc')
            ->get();
        
        return view('empleados.index', compact('empleados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        $empresas = Empresa::all();

        return view('empleados.create', compact(
            'sucursales',
            'empresas'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'documento' => 'required|string|max:50|unique:empleados,documento',
            'edad' => 'required|integer|min:18|max:90',
            'correo' => 'required|email|max:150|unique:empleados,correo|unique:users,email',
            'direccion' => 'required|string|max:255',
            'id_puesto' => 'required|exists:puestos_trabajos,id',
            'id_depto' => 'required|exists:departamentos,id',
            'id_sucursal' => 'required|exists:sucursales,id',
            'id_empresa' => 'required|exists:empresas,id',
            'login' => 'required|in:0,1',
            'estado' => 'required|in:0,1',
            'id_rol' => 'required_if:login,1|exists:roles,id',
        ], [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'edad.min' => 'El empleado debe tener al menos 18 años.',
            'edad.max' => 'La edad no puede superar los 90 años.',

            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Debes ingresar un correo válido.',
            'correo.unique' => 'Este correo ya está registrado en empleados o usuarios.',

            'id_puesto.required' => 'Debes seleccionar un puesto.',
            'id_puesto.exists' => 'El puesto seleccionado no existe.',

            'id_depto.required' => 'Debes seleccionar un departamento.',
            'id_depto.exists' => 'El departamento seleccionado no existe.',

            'id_sucursal.required' => 'Debes seleccionar una sucursal.',
            'id_sucursal.exists' => 'La sucursal seleccionada no existe.',

            'id_empresa.required' => 'Debes seleccionar una empresa.',
            'id_empresa.exists' => 'La empresa seleccionada no existe.',

            'login.required' => 'Debes indicar si el empleado tendrá acceso al sistema.',
            'login.in' => 'Valor inválido para login.',

            'estado.required' => 'Debes seleccionar un estado.',
            'estado.in' => 'Valor inválido para el estado.',
        ]);

        $codigo = $this->armaCodigo($validated['nombres'], $validated['apellidos']);

        // Crear empleado (aún sin user_id)
        $empleado = Empleado::create([
            'cod_trabajador' => 'TEMP',
            'correo' => $validated['correo'],
            'direccion' => $validated['direccion'],
            'edad' => $validated['edad'],
            'documento' => $validated['documento'],
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'id_puesto' => $validated['id_puesto'],
            'id_depto' => $validated['id_depto'],
            'id_sucursal' => $validated['id_sucursal'],
            'id_empresa' => $validated['id_empresa'],
            'login' => $validated['login'],
            'estado' => $validated['estado'],
            'creado_por_usuario' => Auth::id(),
        ]);

        // Actualizar código con ID real
        $empleado->update([
            'cod_trabajador' => $empleado->id.$codigo,
        ]);

        // Si requiere login → crear usuario
        if ($validated['login'] == 1) {

            $passwordTemporal = Str::random(10);

            $user = User::create([
                'name' => $empleado->nombres.' '.$empleado->apellidos,
                'email' => $empleado->correo,
                'password' => Hash::make($passwordTemporal),
                'id_rol' => $validated['id_rol'],
                'id_empleado' => $empleado->id,
            ]);

            // Enviar contraseña por correo
            Mail::to($empleado->correo)
                ->send(new EmpleadoPasswordMail(
                    $user->email,
                    $passwordTemporal
                ));
        }
        $msj = '';
        if ($validated['login'] == 1) {
            $msj = 'Empleado creado correctamente. Se ha enviado un correo con las credenciales de acceso.';
        } else {
            $msj = 'Empleado sin acceso al sistema creado correctamente.';
        }

        return redirect()
            ->route('empleados.create')
            ->with('success', $msj);
    }

    private function armaCodigo($nombres, $apellidos)
    {

        $nombres = explode(' ', trim($nombres));
        $inicialesNombres = '';

        foreach ($nombres as $n) {
            if ($n !== '') {
                $n = remove_accents($n);
                $inicialesNombres .= strtoupper(substr($n, 0, 1));
            }
        }

        $apellidos = explode(' ', trim($apellidos));
        $inicialesApellidos = '';

        foreach ($apellidos as $a) {
            if ($a !== '') {
                $a = remove_accents($a); // o remove_accents($a)
                $inicialesApellidos .= strtoupper(substr($a, 0, 1));
            }
        }

        return $inicialesNombres.$inicialesApellidos;

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $empleado = Empleado::with(['puesto', 'departamento', 'sucursal', 'empresa', 'horarios', 'user.rol'])
            ->findOrFail($id);

        return response()->json($empleado);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $empleado = Empleado::visiblePara(Auth::user())->findOrFail($id);
        $sucursales = Sucursal::all();
        $empresas = Empresa::all();

        return view('empleados.edit', compact(
            'empleado',
            'sucursales',
            'empresas',
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
            $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'documento' => 'required|string|max:50',
            'edad' => 'required|integer|min:18|max:90',
            'correo' => 'required|email|max:150',
            'direccion' => 'required|string|max:255',
            'id_puesto' => 'required|exists:puestos_trabajos,id',
            'id_depto' => 'required|exists:departamentos,id',
            'id_sucursal' => 'required|exists:sucursales,id',
            'id_empresa' => 'required|exists:empresas,id',
            'login' => 'required|in:0,1',
            'estado' => 'required|in:0,1',
            'id_rol' => 'required_if:login,1|exists:roles,id',
        ], [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'edad.min' => 'El empleado debe tener al menos 18 años.',
            'edad.max' => 'La edad no puede superar los 90 años.',

            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Debes ingresar un correo válido.',
            'correo.unique' => 'Este correo ya está registrado en empleados o usuarios.',

            'id_puesto.required' => 'Debes seleccionar un puesto.',
            'id_puesto.exists' => 'El puesto seleccionado no existe.',

            'id_depto.required' => 'Debes seleccionar un departamento.',
            'id_depto.exists' => 'El departamento seleccionado no existe.',

            'id_sucursal.required' => 'Debes seleccionar una sucursal.',
            'id_sucursal.exists' => 'La sucursal seleccionada no existe.',

            'id_empresa.required' => 'Debes seleccionar una empresa.',
            'id_empresa.exists' => 'La empresa seleccionada no existe.',

            'login.required' => 'Debes indicar si el empleado tendrá acceso al sistema.',
            'login.in' => 'Valor inválido para login.',

            'estado.required' => 'Debes seleccionar un estado.',
            'estado.in' => 'Valor inválido para el estado.',
        ]);

        if(Empleado::where('correo', $validated['correo'])->where('id', '!=', $id)->exists()){
            return back()->withErrors(['correo' => 'Este correo ya está registrado en otro empleado.'])->withInput();
        }
        $empleado = Empleado::findOrFail($id);
        $empleado->update($validated);

        if($empleado->login == 1){
            $user = User::where('id_empleado', $empleado->id)->first();
            if(!$user){
                $passwordTemporal = Str::random(10);

                $user = User::create([
                    'name' => $empleado->nombres.' '.$empleado->apellidos,
                    'email' => $empleado->correo,
                    'password' => Hash::make($passwordTemporal),
                    'id_rol' => $validated['id_rol'],
                    'id_empleado' => $empleado->id,
                ]);

                // Enviar contraseña por correo
                Mail::to($empleado->correo)
                    ->send(new EmpleadoPasswordMail(
                        $user->email,
                        $passwordTemporal
                    ));
            }else{
                // Actualizar rol si ya tiene usuario
                $user->update([
                    'id_rol' => $validated['id_rol'],
                    'email' => $empleado->correo,
                    'name' => $empleado->nombres.' '.$empleado->apellidos,
                ]);
            }
        }
        return redirect()
            ->route('empleados.index')
            ->with('success', 'Empleado '. $empleado->cod_trabajador.' actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $empleado = Empleado::findOrFail($id);
        $empleado->update(['estado' => 0]);

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Empleado '. $empleado->cod_trabajador.' inactivado correctamente.');
    }
}
