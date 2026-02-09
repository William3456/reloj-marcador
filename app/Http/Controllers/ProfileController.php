<?php

namespace App\Http\Controllers;

use App\Models\Departamento\Departamento;
use App\Models\Empresa\Empresa;
use App\Models\Puesto\Puesto;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $sucursal = null;
        $empresa = null;
        $depto = null;
        $puesto = null;
        if (Auth::user()->id != 1) {
            $sucursal = Sucursal::find($request->user()->empleado->id_sucursal);
            $empresa = Empresa::find($sucursal->id_empresa);
            $depto = Departamento::find($request->user()->empleado->id_depto);
            $puesto = Puesto::find($request->user()->empleado->id_puesto);
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'sucursal' => $sucursal,
            'empresa' => $empresa,
            'departamento' => $depto,
            'puesto' => $puesto,

        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        // 1. Validamos los datos del empleado (hacemos name y email opcionales aquí si no vienen)
        $request->validate([
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            // Mantenemos validación de usuario por si acaso envías el nombre alguna vez
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $user = $request->user();

        // 2. Actualizamos datos del USUARIO (Solo si se enviaron en el formulario)
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
        }
        $user->save();

        // 3. Actualizamos datos del EMPLEADO (Aquí está la magia que te faltaba)
        // Verificamos si el usuario tiene un empleado asociado
        if ($user->empleado) {
            $user->empleado->update([
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
