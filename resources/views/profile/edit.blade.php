<x-app-layout title="Perfil de Usuario">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <i class="fa-solid fa-id-badge text-blue-600"></i>
            {{ __('Mi perfil') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                // 1. PREPARACIÓN DE DATOS (Lógica Unificada)
                $usuario = Auth::user();
                $datosEmpleado = $usuario->empleado; // Relación
                $esAdmin = $usuario->id_rol == 1;

                // Nombres y Apellidos
                if ($datosEmpleado) {
                    $nombrePrincipal = $datosEmpleado->nombres;
                    $subTexto = $datosEmpleado->apellidos;
                } else {
                    $nombrePrincipal = !empty($usuario->name) ? $usuario->name : 'Usuario Administrador';
                    $subTexto = '';
                }
            @endphp

            {{-- 2. VERIFICACIÓN DE PERMISO --}}
            @if($datosEmpleado || $esAdmin)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- COLUMNA 1: IDENTIDAD Y DATOS CORPORATIVOS --}}
                    <div class="lg:col-span-1 space-y-6">

                        {{-- TARJETA DE PERFIL (DISEÑO FLEX - AVATAR A LA IZQUIERDA) --}}
                        <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
                            <div class="flex items-center gap-5">
                                {{-- Avatar Pequeño a la Izquierda (Sin position absolute) --}}
                                <div class="shrink-0">
                                    <div
                                        class="w-20 h-20 rounded-full {{ $esAdmin && !$datosEmpleado ? 'bg-gray-800 text-white' : 'bg-blue-100 text-blue-600' }} flex items-center justify-center text-3xl shadow-sm">
                                        @if($esAdmin && !$datosEmpleado)
                                            <i class="fa-solid fa-user-shield"></i>
                                        @else
                                            <i class="fa-solid fa-user-tie"></i>
                                        @endif
                                    </div>
                                </div>

                                {{-- Información del Usuario --}}
                                <div class="overflow-hidden">
                                    <h3 class="text-lg font-bold text-gray-800 leading-tight truncate">
                                        {{ $nombrePrincipal }}
                                    </h3>
                                    @if($subTexto)
                                        <p class="text-sm text-gray-500 font-medium truncate">{{ $subTexto }}</p>
                                    @endif

                                    {{-- Badges --}}
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if($datosEmpleado)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                #{{ $datosEmpleado->cod_trabajador }}
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                                Activo
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-100">
                                                   @if($usuario->id_rol == 2)
                                                        Encargado de sucursal
                                                    @elseif($usuario->id_rol == 3)
                                                         Empleado
                                                    @endif
                                                
                                            </span>
                                        @endif
                                        @if($esAdmin)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-100">
                                                Super administrador
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <hr class="my-5 border-gray-100">

                            {{-- Datos Personales Básicos --}}
                            <div class="space-y-4">
                                @if($datosEmpleado)
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                            <i class="fa-regular fa-id-card"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase">Documento</p>
                                            <p class="text-sm font-semibold text-gray-700">{{ $datosEmpleado->documento }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                            <i class="fa-solid fa-cake-candles"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase">Fecha Nacimiento</p>
                                            <p class="text-sm font-semibold text-gray-700">
                                                {{ \Carbon\Carbon::parse($datosEmpleado->fecha_nacimiento)->locale('es')->isoFormat('D [de] MMMM YYYY') }}</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-bold text-gray-400 uppercase">Correo Acceso</p>
                                        <p class="text-sm font-semibold text-gray-700 truncate"
                                            title="{{ $usuario->email }}">{{ $usuario->email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- FICHA CORPORATIVA (Movida aquí para mejor estructura vertical en móvil) --}}
                        @if($datosEmpleado)
                            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-5">
                                <h4
                                    class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                                    Asignación Corporativa
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    {{-- Empresa --}}
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-xs text-gray-400 mb-1">Empresa</p>
                                        <p class="text-sm font-bold text-gray-800 leading-tight">
                                            {{ $empresa->nombre }}</p>
                                    </div>
                                    {{-- Sucursal --}}
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-xs text-gray-400 mb-1">Sucursal</p>
                                        <p class="text-sm font-bold text-gray-800 leading-tight">
                                            {{ $sucursal->nombre }}</p>
                                    </div>
                                    {{-- Departamento --}}
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-xs text-gray-400 mb-1">Depto</p>
                                        <p class="text-sm font-bold text-gray-800 leading-tight">{{ $departamento->nombre_depto }}
                                        </p>
                                    </div>
                                    {{-- Puesto --}}
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-xs text-gray-400 mb-1">Puesto</p>
                                        <p class="text-sm font-bold text-gray-800 leading-tight">{{ $puesto->desc_puesto }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- COLUMNA 2 y 3: FORMULARIOS DE EDICIÓN --}}
                    <div class="lg:col-span-2 space-y-6">

                        {{-- FORMULARIO DE CONTACTO --}}
                        {{-- FORMULARIO DE CONTACTO --}}
                        @if($datosEmpleado)
                            <div class="bg-white shadow-lg rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fa-solid fa-address-book text-blue-500"></i> Información de Contacto
                                    </h3>
                                    <span
                                        class="text-xs font-semibold bg-green-100 text-green-700 px-2 py-1 rounded">Editable</span>
                                </div>

                                <div class="p-6">
                                    {{-- MUESTRA ERRORES SI LOS HAY --}}
                                    @if ($errors->any())
                                        <div class="mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>• {{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                                        @csrf
                                        @method('patch')

                                        {{-- TRUCO: Si tu Request original es muy estricto y no quieres cambiar el controlador a
                                        fondo,
                                        puedes descomentar esto para enviar los datos viejos y "engañar" al validador,
                                        pero la solución del Paso 1 es más limpia. --}}
                                        {{-- <input type="hidden" name="name" value="{{ $usuario->name }}"> --}}
                                        {{-- <input type="hidden" name="email" value="{{ $usuario->email }}"> --}}

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                            {{-- Teléfono --}}
                                            <div>
                                                <x-input-label for="telefono" :value="__('Teléfono Móvil')"
                                                    class="mb-1 text-xs uppercase text-gray-500" />
                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                        <i class="fa-solid fa-mobile-screen"></i>
                                                    </div>
                                                    <input type="text" name="telefono" id="telefono"
                                                        value="{{ old('telefono', $datosEmpleado->telefono) }}"
                                                        class="pl-10 block w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                        placeholder="0000-0000">
                                                </div>
                                            </div>

                                            {{-- Dirección --}}
                                            <div class="md:col-span-2">
                                                <x-input-label for="direccion" :value="__('Dirección Domiciliar')"
                                                    class="mb-1 text-xs uppercase text-gray-500" />
                                                <div class="relative">
                                                    <div class="absolute top-3 left-3 text-gray-400 pointer-events-none">
                                                        <i class="fa-solid fa-map-location-dot"></i>
                                                    </div>
                                                    <textarea name="direccion" id="direccion" rows="2"
                                                        class="pl-10 block w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                        placeholder="Ingrese dirección completa">{{ old('direccion', $datosEmpleado->direccion) }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end pt-2">
                                            @if (session('status') === 'profile-updated')
                                                <span x-data="{ show: true }" x-show="show" x-transition
                                                    x-init="setTimeout(() => show = false, 3000)"
                                                    class="text-sm text-green-600 font-medium mr-3">
                                                    <i class="fa-solid fa-check mr-1"></i> Guardado
                                                </span>
                                            @endif
                                            <button type="submit"
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm shadow transition transform hover:-translate-y-0.5">
                                                Actualizar Datos
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        {{-- SEGURIDAD (PASSWORD) --}}
                        <div class="bg-white shadow-lg rounded-2xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-red-500"></i> Seguridad de la Cuenta
                                </h3>
                            </div>

                            <div class="p-6">
                                <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                                    @csrf
                                    @method('put')

                                    <div>
                                        <x-input-label for="current_password" :value="__('Contraseña Actual')"
                                            class="mb-1 text-xs uppercase text-gray-500" />
                                        <input type="password" name="current_password" id="current_password"
                                            autocomplete="current-password"
                                            class="block w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                        <x-input-error :messages="$errors->updatePassword->get('current_password')"
                                            class="mt-1" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <x-input-label for="password" :value="__('Nueva Contraseña')"
                                                class="mb-1 text-xs uppercase text-gray-500" />
                                            <input type="password" name="password" id="password" autocomplete="new-password"
                                                class="block w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                            <x-input-error :messages="$errors->updatePassword->get('password')"
                                                class="mt-1" />
                                        </div>

                                        <div>
                                            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')"
                                                class="mb-1 text-xs uppercase text-gray-500" />
                                            <input type="password" name="password_confirmation" id="password_confirmation"
                                                autocomplete="new-password"
                                                class="block w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')"
                                                class="mt-1" />
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end pt-2">
                                        @if (session('status') === 'password-updated')
                                            <span x-data="{ show: true }" x-show="show" x-transition
                                                x-init="setTimeout(() => show = false, 3000)"
                                                class="text-sm text-green-600 font-medium mr-3">
                                                <i class="fa-solid fa-check mr-1"></i> Actualizada
                                            </span>
                                        @endif
                                        <button type="submit"
                                            class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm shadow transition transform hover:-translate-y-0.5">
                                            Cambiar Contraseña
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            @else
                {{-- ESTADO DE ERROR (Sin empleado ni admin) --}}
                <div class="max-w-3xl mx-auto mt-10">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm flex items-start">
                        <i class="fa-solid fa-triangle-exclamation text-yellow-400 text-xl mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800">Perfil Incompleto</h3>
                            <p class="mt-1 text-sm text-yellow-700">Usuario colaborador sin expediente asociado.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<script>
    document.getElementById('telefono').addEventListener('input', function (e) {
        let valor = e.target.value.replace(/\D/g, ''); // solo números
        if (valor.length > 4) {
            valor = valor.slice(0, 4) + ' ' + valor.slice(4, 8);
        }
        e.target.value = valor;
    });
</script>