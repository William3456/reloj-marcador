<x-app-layout title="Marcación de Asistencia">
    <x-slot name="header">
        {{-- MODIFICACIÓN 1: Header flexible para incluir el botón de info sin mover el título --}}
        <div class="relative flex items-center justify-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Registro de Asistencia') }}
            </h2>
            
            {{-- Botón Trigger del Modal --}}
            <button onclick="toggleModal('modal-sucursal')" class="absolute right-0 p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-full transition-colors focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>
    </x-slot>

<div class="py-6 px-4"> 
        <div class="max-w-md mx-auto space-y-6"> 

            {{-- RELOJ --}}
            <div class="bg-white shadow-lg rounded-2xl p-6 text-center border-t-4 border-blue-600 relative overflow-hidden">
                <div class="absolute top-3 right-3 opacity-10">
                    <svg class="w-12 h-12 text-blue-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                </div>
                <p class="text-gray-500 text-xs uppercase tracking-widest font-bold mb-1 relative z-10">Hora Actual</p>
                <div id="reloj-tiempo-real" class="text-5xl font-black text-gray-800 tracking-tight relative z-10">--:--:--</div>
                <p class="text-blue-600 font-medium text-sm mt-2 uppercase relative z-10" id="fecha-actual">
                    {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                </p>
            </div>

            {{-- MENSAJES --}}
            @if (session('success'))
                <div class="p-4 rounded-xl bg-green-50 border-l-4 border-green-500 text-green-700 shadow-sm mb-6"><p class="font-bold">¡Excelente!</p><p class="text-sm">{{ session('success') }}</p></div>
            @elseif (session('error'))
                <div class="p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 shadow-sm mb-6"><p class="font-bold">Error</p><p class="text-sm">{{ session('error') }}</p></div>
            @endif
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 shadow-sm mb-6">
                    <ul class="list-disc list-inside text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- =========================================================== --}}
            {{-- LÓGICA VISUAL PRINCIPAL --}}
            {{-- =========================================================== --}}
            {{-- CASO 0: PERMISO ACTIVO (EXIME MARCACIÓN) --}}
            @if(isset($permisoActivo) && $permisoActivo)

                <div class="bg-green-500 rounded-2xl shadow-xl overflow-hidden text-white relative mb-4">
                    <div class="p-8 text-center relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Permiso Asignado</h3>
                        <p class="text-green-100 text-sm">Tienes un permiso activo para hoy: {{ $permisoActivo->tipoPermiso->nombre ?? 'Permiso Especial' }}</p>
                        <p class="text-xs text-green-200 mt-4">No es necesario marcar asistencia.</p>
                    </div>
                </div>
            {{-- CASO 1: ESPERA (Hay turno futuro, pero falta mucho) --}}
            @elseif(isset($tiempoRestante) && !$habilitarEntrada)
                <div class="bg-indigo-600 rounded-2xl shadow-xl overflow-hidden text-white relative mb-4">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-indigo-500 rounded-full opacity-50 blur-xl"></div>
                    <div class="p-8 text-center relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                            <i class="far fa-clock text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Próximo Turno</h3>
                        <p class="text-indigo-100 text-sm mb-4">Inicia a las <span class="font-bold text-white">{{ $proximoHorario->format('H:i') }}</span></p>
                        <div class="bg-indigo-800/50 rounded-lg p-3 inline-block">
                            <span class="text-xs uppercase tracking-widest text-indigo-300">Faltan</span>
                            <div class="text-xl font-bold text-white">{{ $tiempoRestante }}</div>
                        </div>
                        <p class="text-xs text-indigo-200 mt-6">Podrás marcar entrada 30 minutos antes.</p>
                    </div>
                </div>

            {{-- CASO 2: JORNADA FINALIZADA (No hay turno futuro y ya se trabajó hoy) --}}
            @elseif($jornadaTerminada)
                <div class="bg-green-600 rounded-2xl shadow-xl overflow-hidden text-white relative mb-4">
                    <div class="p-8 text-center relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">¡Jornada Completada!</h3>
                        <p class="text-green-100 text-sm">Has finalizado todos tus turnos de hoy.</p>
                        <p class="text-xs text-green-200 mt-4">Nos vemos en tu próximo turno.</p>
                    </div>
                </div>

            {{-- CASO 3: FORMULARIO ACTIVO (Entrada habilitada o Salida pendiente) --}}
            @else
                @if($candidatos->isNotEmpty() || $entradaActiva) {{-- Solo mostrar form si hay turnos --}}
                    <div class="bg-white shadow-xl rounded-2xl overflow-hidden mb-6">
                        <div class="p-6">
                            @php
                                $mostrarForm = true;
                                if ($entradaActiva && $horarioRequiereSalida == 0) $mostrarForm = false;
                            @endphp
                        <form action="{{ route('marcacion.store') }}" method="POST" enctype="multipart/form-data" id="form-marcacion">
                            @csrf
                            <input type="hidden" name="latitud" id="latitud">
                            <input type="hidden" name="longitud" id="longitud">
                            <input type="hidden" name="ubicacion" id="ubicacion_texto">

                            <div class="mb-6">
                                <label class="block text-sm font-bold text-gray-700 mb-3">Tipo de Registro</label>
                                
                                @if(!$entradaActiva)
                                    {{-- BOTÓN ENTRADA --}}
                                    <label class="cursor-pointer group relative">
                                        <input type="radio" name="tipo_marcacion" value="1" class="peer sr-only" checked>
                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-blue-100 bg-blue-50 rounded-xl transition-all duration-200 peer-checked:border-blue-500 peer-checked:shadow-md hover:bg-blue-100">
                                            <div class="w-14 h-14 bg-blue-500 text-white rounded-full flex items-center justify-center mb-3 shadow-sm animate-pulse">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                            </div>
                                            <span class="font-bold text-xl text-blue-800">MARCAR ENTRADA</span>
                                            <span class="text-sm text-blue-600 mt-1">Iniciar jornada</span>
                                        </div>
                                    </label>
                                @elseif($entradaActiva && $horarioRequiereSalida == 1)
                                    {{-- BOTÓN SALIDA --}}
                                    <label class="cursor-pointer group relative">
                                        <input type="radio" name="tipo_marcacion" value="2" class="peer sr-only" checked>
                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-red-100 bg-red-50 rounded-xl transition-all duration-200 peer-checked:border-red-500 peer-checked:shadow-md hover:bg-red-100">
                                            <div class="w-14 h-14 bg-red-500 text-white rounded-full flex items-center justify-center mb-3 shadow-sm">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                            </div>
                                            <span class="font-bold text-xl text-red-800">MARCAR SALIDA</span>
                                            <span class="text-sm text-red-600 mt-1">Entrada: {{ $entradaActiva->created_at->format('H:i') }}</span>
                                        </div>
                                    </label>
                                @else
                                    <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                                        <h3 class="text-lg font-bold text-green-800">Asistencia Registrada</h3>
                                        <p class="text-green-600 text-sm">Entrada: {{ $entradaActiva->created_at->format('H:i') }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($mostrarForm)
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <div class="flex items-center"><span id="gps-status" class="text-sm text-gray-500 font-medium">Buscando GPS...</span></div>
                                        <div id="gps-accuracy" class="text-xs text-gray-400"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-3">Evidencia</label>
                                        <div class="relative w-full h-48 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center overflow-hidden group hover:border-blue-400 transition-colors">
                                            <img id="preview-foto" class="absolute inset-0 w-full h-full object-cover hidden" />
                                            <div id="placeholder-foto" class="text-center p-4"><p class="text-sm text-gray-500 font-medium">Tocar para tomar foto</p></div>
                                            <input type="file" name="ubi_foto" id="input-foto" accept="image/*" capture="user" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="previewImage(event)">
                                        </div>
                                    </div>
                                    <button type="submit" id="btn-marcar" disabled class="w-full text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-xl text-lg px-5 py-4 text-center shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">Registrar Marcación</button>
                                </div>
                            @endif
                        </form>
                @else
                        {{-- CASO: DÍA LIBRE (Sin turnos asignados hoy) --}}
                    <div class="bg-gray-100 rounded-2xl p-8 text-center border-2 border-dashed border-gray-300">
                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-500">Sin Turnos Asignados</h3>
                        <p class="text-gray-400 text-sm mt-2">No tienes horarios programados para hoy.</p>
                    </div>
                @endif  
                    </div>
                </div>
            @endif

            {{-- =========================================================== --}}
            {{-- SECCIÓN RESUMEN (AHORA SIEMPRE VISIBLE SI HAY DATOS) --}}
            {{-- =========================================================== --}}
            @if($historialHoy->isNotEmpty())
                <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="font-bold text-gray-700 text-sm">Resumen de Actividad (Hoy)</h4>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach($historialHoy as $registro)
                            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="p-2 rounded-full mr-3 {{ $registro->tipo_marcacion == 1 ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600' }}">
                                        @if($registro->tipo_marcacion == 1)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ $registro->tipo_marcacion == 1 ? 'Entrada' : 'Salida' }}</p>
                                        <p class="text-xs text-gray-500">
                                            @if($registro->horario) 
                                                Turno {{ \Carbon\Carbon::parse($registro->horario->hora_ini)->format('H:i') }} 
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-700">{{ $registro->created_at->format('H:i') }}</p>
                                    @if($registro->fuera_horario)
                                        <span class="text-[10px] text-orange-500 font-bold block">Tarde/Olvido</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- MODIFICACIÓN 2: Modal de Información de Sucursal (Fuera del flujo principal) --}}
    <div id="modal-sucursal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop oscuro --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="toggleModal('modal-sucursal')"></div>

        {{-- Contenedor del Modal --}}
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xs w-full max-w-sm">
                    
                    {{-- Cabecera Modal --}}
                    <div class="bg-blue-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                        <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">Mi sucursal asignada</h3>
                        <button onclick="toggleModal('modal-sucursal')" class="text-white hover:text-gray-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Cuerpo del Modal --}}
                    <div class="px-4 py-5 sm:p-6 space-y-4">
                        {{-- Icono central --}}
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                            <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>

                        <div class="text-center">
                            {{-- DATOS DINÁMICOS DE SUCURSAL --}}
                            {{-- Asegúrate de que el usuario tenga relación con sucursal, usa optional() o ?? para evitar errores --}}
                            <h4 class="text-lg font-bold text-gray-900">
                                
                                {{ Auth::user()->empleado->sucursal->nombre ?? 'Sin Sucursal Asignada' }}
                            </h4>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ Auth::user()->empleado->sucursal->direccion ?? 'Ponte en contacto con RRHH para asignación.' }}
                            </p>
                        </div>
                        
                        {{-- Información extra en lista --}}
                        <div class="bg-gray-50 rounded-lg p-3 text-sm text-left space-y-2 border border-gray-100">
    
    {{-- SECCIÓN HORARIOS (MODIFICADA: Días en vez de Turno) --}}
    <div class="flex flex-col space-y-2">
        <span class="text-gray-500 font-medium text-xs uppercase mb-1">Horarios de Atención:</span>
        
        @forelse(Auth::user()->empleado->sucursal->horarios as $h)
            <div class="flex flex-col bg-white border border-gray-200 rounded px-2 py-2 shadow-sm">
                {{-- 1. Hora Inicio - Fin --}}
                <div class="flex items-center mb-1">
                    <i class="far fa-clock text-blue-500 mr-1.5 text-xs"></i>
                    <span class="font-bold text-gray-700">
                        {{ \Carbon\Carbon::parse($h->hora_ini)->format('H:i') }} - {{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}
                    </span>
                </div>

                {{-- 2. Días del Horario (Reemplaza al turno_txt) --}}
                <div class="flex flex-wrap gap-1">
                    @foreach($h->dias ?? [] as $dia)
                        <span class="text-[10px] bg-gray-100 text-gray-600 border border-gray-200 px-1.5 py-0.5 rounded capitalize">
                            {{ mb_substr($dia, 0, 3) }} {{-- Abreviamos a 3 letras (Lun, Mar...) --}}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <span class="text-gray-400 italic text-xs">Sin horarios asignados</span>
        @endforelse
    </div>

    <div class="border-t border-gray-200 my-2"></div>

    {{-- SECCIÓN TELÉFONO --}}
    <div class="flex justify-between items-center">
        <span class="text-gray-500">Teléfono:</span>
        <span class="font-medium text-gray-800">{{ Auth::user()->empleado->sucursal->telefono ?? 'N/A' }}</span>
    </div>

    {{-- SECCIÓN DÍAS LABORALES GENERALES (Opcional: Si quieres mantener el resumen global abajo) --}}
    <div class="flex flex-col space-y-2 pt-1">
        <span class="text-gray-500 text-xs text-center font-bold">Días operativos (Sucursal):</span>
        <div class="flex flex-wrap gap-1 justify-center">
            @foreach(Auth::user()->empleado->sucursal->dias_laborales ?? [] as $dia)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 capitalize">
                    {{ $dia }}
                </span>
            @endforeach
        </div>
    </div>
</div>
                    </div>

                    {{-- Footer Modal --}}
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="toggleModal('modal-sucursal')">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{-- MODAL BLOQUEANTE: Marcación Pendiente --}}
@if(isset($mostrarModalBloqueo) && $mostrarModalBloqueo)
<div id="modal-bloqueo" class="fixed inset-0 z-[100] overflow-y-auto">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-95 backdrop-blur-md"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all w-full max-w-sm border-2 border-red-100">
            
            <div class="bg-red-600 p-4 text-center">
                <h3 class="text-xl font-black text-white">¡Atención!</h3>
                <p class="text-red-100 text-xs">Tienes un turno abierto que debes cerrar</p>
            </div>
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 shadow-sm mb-6">
                    <div class="flex items-center mb-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p class="font-bold">Atención</p>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->getMessages() as $key => $messages)
                        @if ($key !== 'ubi_foto') {{-- <-- AQUÍ FILTRAMOS QUE NO SE MUESTRE --}}
                            @foreach ($messages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        @endif
                    @endforeach
                    </ul>
                </div>
            @endif
            <div class="p-6 text-center">
                <p class="text-gray-600 text-sm mb-4">
                    Turno del: <span class="font-bold text-gray-900">{{ $marcacionPendiente->created_at->format('d/m/Y H:i') }}</span>
                </p>

                <form action="{{ route('marcacion.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tipo_marcacion" value="2">
                    <input type="hidden" name="latitud" class="lat-bloqueo">
                    <input type="hidden" name="longitud" class="lng-bloqueo">

                    {{-- NUEVO: Input de Foto para el Modal --}}
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Evidencia de salida</label>
                        <div class="relative w-full h-40 bg-gray-50 rounded-2xl border-2 border-dashed border-red-200 flex flex-col items-center justify-center overflow-hidden group">
                            <img id="preview-foto-modal" class="absolute inset-0 w-full h-full object-cover hidden" />
                            
                            <div id="placeholder-modal" class="text-center">
                                <div class="w-10 h-10 mx-auto bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                </div>
                                <p class="text-xs text-red-400 font-bold">Tocar para tomar foto</p>
                            </div>

                            <input type="file" name="ubi_foto" accept="image/*" capture="user" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="previewImageModal(event)">
                        </div>
                    </div>

                    <button type="submit" id="btn-marcar-modal" class="w-full bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-red-700 transition-all active:scale-95 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        REGISTRAR SALIDA AHORA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@push('scripts')
<script>
    const MODO_PRUEBAS = true; // Cambiar a true solo para desarrollo
    const PRECISION_REQUERIDA = 100; // Metros aceptables

    // Ubicación fija de prueba
    const UBICACION_FAKE = {
        latitude: 13.69696,
        longitude: -89.24584,
        accuracy: 5
    };

    const btnMarcar = document.getElementById('btn-marcar');
    const statusGps = document.getElementById('gps-status');
    const inputLat = document.getElementById('latitud');
    const inputLng = document.getElementById('longitud');
    const gpsAccuracyText = document.getElementById('gps-accuracy');
    const inputFoto = document.getElementById('input-foto');

    // Variables de estado
    let gpsValido = false;
    let fotoValida = false;

    function toggleModal(modalID) {
        document.getElementById(modalID).classList.toggle("hidden");
    }

    // --- RELOJ EN TIEMPO REAL ---
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const el = document.getElementById('reloj-tiempo-real');
        if(el) el.textContent = `${horas}:${minutos}:${segundos}`;
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();

    // --- GEOLOCALIZACIÓN ---
    const options = { 
        enableHighAccuracy: true, 
        timeout: 15000, 
        maximumAge: 0 
    };

    if (MODO_PRUEBAS) {
        setTimeout(aplicarUbicacionFake, 1000);
    } else if ("geolocation" in navigator) {
        // watchPosition se queda escuchando cambios en la ubicación
        navigator.geolocation.watchPosition(success, error, options);
    } else {
        statusGps.textContent = "GPS no soportado en este navegador";
        statusGps.className = "text-sm text-red-600 font-bold";
    }

    function aplicarUbicacionFake() {
        success({
            coords: {
                latitude: UBICACION_FAKE.latitude,
                longitude: UBICACION_FAKE.longitude,
                accuracy: UBICACION_FAKE.accuracy
            }
        });
    }

    function success(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const acc = Math.round(position.coords.accuracy);

        // 1. Mostrar Precisión al Usuario visualmente
        let colorPrecision = 'text-red-500';
        if(acc <= PRECISION_REQUERIDA) colorPrecision = 'text-green-600';
        else if(acc <= PRECISION_REQUERIDA * 2) colorPrecision = 'text-orange-500';

        gpsAccuracyText.innerHTML = `<span class="${colorPrecision} font-bold"><i class="fa-solid fa-satellite-dish"></i> Margen de error: ${acc} metros</span>`;

        // 2. Evaluar si la precisión es aceptable
        if (acc <= PRECISION_REQUERIDA) {
            // -- SEÑAL BUENA --
            gpsValido = true;
            
            // Llenar inputs ocultos
            inputLat.value = lat;
            inputLng.value = lng;
            
            // Actualizar UI
            statusGps.textContent = "Ubicación Precisa Confirmada";
            statusGps.className = "text-sm font-bold text-green-700";
            
            // Icono estático (ya encontró)
            const iconContainer = document.getElementById('gps-icon');
            if(iconContainer) iconContainer.classList.remove('animate-bounce');

            // Actualizar inputs del modal de bloqueo si existe
            const modalLat = document.querySelector('.lat-bloqueo');
            const modalLng = document.querySelector('.lng-bloqueo');
            if(modalLat) modalLat.value = lat;
            if(modalLng) modalLng.value = lng;

        } else {
            // -- SEÑAL MALA / INESTABLE --
            gpsValido = false;
            
            statusGps.innerHTML = `Mejorando señal... <span class="text-xs text-orange-600">(Acércate a una ventana)</span>`;
            statusGps.className = "text-sm font-bold text-orange-500 animate-pulse";
            
            // Icono animado (buscando)
            const iconContainer = document.getElementById('gps-icon');
            if(iconContainer) iconContainer.classList.add('animate-bounce');
        }

        actualizarEstadoBoton();
    }

    function error(err) {
        console.warn('GPS Error: ' + err.message);
        gpsValido = false;
        statusGps.textContent = "Sin señal GPS. Activa la ubicación.";
        statusGps.className = "text-sm font-bold text-red-600";
        gpsAccuracyText.textContent = "";
        actualizarEstadoBoton();
    }

    // --- FOTOGRAFÍA ---
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('preview-foto');
        const placeholder = document.getElementById('placeholder-foto');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                
                fotoValida = true; // Foto capturada
                actualizarEstadoBoton();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewImageModal(event) {
        // Lógica separada para el modal de bloqueo (no afecta al botón principal)
        const input = event.target;
        const preview = document.getElementById('preview-foto-modal');
        const placeholder = document.getElementById('placeholder-modal');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // --- VALIDACIÓN FINAL ---
    function actualizarEstadoBoton() {
        if (!btnMarcar) return;

        // El botón se habilita SOLO si hay GPS preciso Y Foto tomada
        if (gpsValido && fotoValida) {
            btnMarcar.disabled = false;
            btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
            // Restaurar gradiente original si se quiere, o dejar clases CSS base
        } else {
            btnMarcar.disabled = true;
            btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
</script>
@endpush
</x-app-layout>