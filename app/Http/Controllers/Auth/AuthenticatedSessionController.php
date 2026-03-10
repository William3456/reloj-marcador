<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    // 🌟 PASO CRUCIAL PARA MÓVILES:
    // Forzamos la regeneración y el guardado inmediato en disco/BD
    $request->session()->regenerate();

    // 🌟 DOBLE CANDADO:
    // Obligamos a Laravel a escribir los datos de sesión YA MISMO
    // antes de que el navegador móvil haga la redirección.
    $request->session()->save(); 

    $user = Auth::user();

    // Redirección manual según rol para evitar conflictos con el middleware global
    if ($user->id_rol === 3) {
        return redirect()->intended('/marcacion/inicio');
    }

    return redirect()->intended(route('dashboard', absolute: false));
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login?r=' . uniqid());
    }
}
