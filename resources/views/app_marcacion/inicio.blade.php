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
                        <p class="text-xs text-indigo-200 mt-6">Podrás marcar entrada 1 hora antes.</p>
                    </div>
                </div>
            @elseif(isset($ausenteTotal) && $ausenteTotal)
                <div class="bg-red-600 rounded-2xl shadow-xl overflow-hidden text-white relative mb-4">
                    <div class="p-8 text-center relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Jornada Finalizada</h3>
                        <p class="text-red-100 text-sm">Tus turnos de hoy han concluido.</p>
                        <p class="text-xs text-red-200 mt-4 font-bold bg-red-800/30 inline-block px-3 py-1 rounded-lg">No registraste asistencia el día de hoy.</p>
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
            {{-- SECCIÓN RESUMEN (DISEÑO HISTORIAL) --}}
            {{-- =========================================================== --}}
            @if($historialHoy->isNotEmpty())
                
                <div class="mt-8 mb-2">
                    <h4 class="font-bold text-gray-700 text-sm px-2 mb-2 uppercase tracking-wider">Actividad de Hoy</h4>
                </div>

                {{-- Agrupamos por Horario para simular la vista de Turnos --}}
                @php
                    // 1. Extraemos los IDs de las ENTRADAS que sí se hicieron hoy
                    $entradasHoyIds = $historialHoy->where('tipo_marcacion', 1)->pluck('id')->toArray();

                    // 2. Agrupamos por ID de horario, aislando los "Olvidos" de días anteriores
                    $registrosPorTurno = $historialHoy->groupBy(function($item) use ($entradasHoyIds) {
                        // Si es Salida (2), tiene un ID de Entrada, y esa entrada NO está en las de hoy...
                        if ($item->tipo_marcacion == 2 && $item->id_marcacion_entrada && !in_array($item->id_marcacion_entrada, $entradasHoyIds)) {
                            return 'olvido_pasado_' . $item->id; // Lo metemos en un grupo único aislado
                        }
                        return $item->id_horario ? 'turno_' . $item->id_horario : 'extra';
                    });
                @endphp

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    
                    @foreach($registrosPorTurno as $horarioId => $registros)
                        @php
                            // Identificamos si este grupo es un Olvido del Pasado
                            $esOlvidoPasado = str_starts_with($horarioId, 'olvido_pasado_');
                            
                            if ($esOlvidoPasado) {
                                $tituloTurno = 'Registro Atrasado (Cierre de Turno)';
                            } else {
                                $horarioRef = $registros->first()->horario;
                                $tituloTurno = $horarioRef 
                                    ? 'Turno • ' . \Carbon\Carbon::parse($horarioRef->hora_ini)->format('H:i') . ' - ' . \Carbon\Carbon::parse($horarioRef->hora_fin)->format('H:i')
                                    : 'Marcaciones Adicionales';
                            }
                        @endphp

                        {{-- CABECERA DEL TURNO --}}
                        <div class="px-4 py-1.5 border-b border-gray-100 border-t {{ $loop->first ? 'border-t-0' : 'border-t-gray-100' }} flex justify-between items-center {{ $esOlvidoPasado ? 'bg-red-50' : 'bg-gray-50/50' }}">
                            <span class="text-[10px] font-bold uppercase tracking-wider {{ $esOlvidoPasado ? 'text-red-500' : 'text-gray-400' }}">
                                @if($esOlvidoPasado) <i class="fa-solid fa-triangle-exclamation mr-1 animate-pulse"></i> @endif
                                {{ $tituloTurno }}
                            </span>
                        </div>

                        {{-- LISTA DE MARCACIONES DEL TURNO --}}
                        @foreach($registros->sortBy('created_at') as $reg)
                            @php
                                $tipoTexto = $reg->tipo_marcacion == 1 ? 'Entrada' : 'Salida';
                                
                                // Si es el olvido pasado, el icono será rojo intenso para destacar
                                $iconoBg = $reg->tipo_marcacion == 1 ? 'bg-green-100 text-green-600' : ($esOlvidoPasado ? 'bg-red-600 text-white shadow-md' : 'bg-red-100 text-red-600');
                                
                                // Recolector dinámico de Badges
                                $badges = [];
                                
                                // Estado Nativo (Tarde / Olvido normal de hoy)
                                if($reg->tipo_marcacion == 1 && $reg->fuera_horario) { 
                                    $badges[] = ['texto' => 'Tarde', 'color' => 'bg-orange-100 text-orange-700 border-orange-200']; 
                                } elseif($reg->tipo_marcacion == 2 && $reg->fuera_horario && !$esOlvidoPasado) { 
                                    $badges[] = ['texto' => 'Olvido/Extra', 'color' => 'bg-red-100 text-red-700 border-red-200']; 
                                }

                                // BADGE ESPECIAL PARA OLVIDOS DE OTROS DÍAS
                                if ($esOlvidoPasado) {
                                    // Consultamos directo a DB la fecha de la entrada original sin romper relaciones
                                    $fechaEntradaAntigua = \Illuminate\Support\Facades\DB::table('marcaciones_empleados')
                                                            ->where('id', $reg->id_marcacion_entrada)
                                                            ->value('created_at');
                                    
                                    $textoFecha = $fechaEntradaAntigua ? \Carbon\Carbon::parse($fechaEntradaAntigua)->format('d/m/Y') : 'Día Anterior';
                                    $badges[] = ['texto' => 'Turno del: ' . $textoFecha, 'color' => 'bg-red-600 text-white border-red-700 shadow-sm'];
                                }

                                // Permisos Múltiples
                                if(isset($reg->permisos)) {
                                    foreach($reg->permisos as $permiso) {
                                        $nombrePermiso = $permiso->tipoPermiso->nombre ?? 'Permiso';
                                        $badges[] = ['texto' => $nombrePermiso, 'color' => 'bg-blue-100 text-blue-700 border-blue-200'];
                                    }
                                }

                                // Convertir a HTML crudo para enviarlo al Modal
                                $badgesHtml = '';
                                foreach($badges as $b) {
                                    $badgesHtml .= '<span class="text-[10px] font-bold px-1.5 py-0.5 rounded border '.$b['color'].'">'.$b['texto'].'</span>';
                                }
                            @endphp

                            <div onclick="abrirDetalleHistorial(this)"
                                 class="flex items-center p-4 cursor-pointer hover:bg-gray-50 active:bg-blue-50 transition-colors border-b border-gray-50 {{ $esOlvidoPasado ? 'bg-red-50/30' : '' }}"
                                 data-tipo="{{ $tipoTexto }}"
                                 data-hora="{{ $reg->created_at->format('h:i A') }}"
                                 data-fecha="{{ $reg->created_at->locale('es')->isoFormat('dddd, D [de] MMMM') }}"
                                 data-sucursal="{{ $reg->sucursal->nombre ?? 'Ubicación GPS' }}"
                                 data-foto="{{ Storage::url($reg->ubi_foto) }}"
                                 data-lat="{{ $reg->latitud }}"
                                 data-lng="{{ $reg->longitud }}"
                                 data-badges="{{ $badgesHtml }}">
                                
                                {{-- Icono --}}
                                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center {{ $iconoBg }}">
                                    @if($reg->tipo_marcacion == 1)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    @endif
                                </div>

                                {{-- Textos y Múltiples Badges --}}
                                <div class="ml-4 flex-grow">
                                    <div class="flex items-center flex-wrap gap-1.5 mb-0.5">
                                        <p class="text-sm font-bold text-gray-800 mr-1">{{ $tipoTexto }}</p>
                                        @foreach($badges as $badge)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border {{ $badge['color'] }}">
                                                {{ $badge['texto'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ $reg->created_at->format('h:i A') }} • {{ $reg->sucursal->nombre ?? 'GPS' }}
                                    </p>
                                </div>

                                {{-- Chevron --}}
                                <div class="text-gray-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
{{-- MODAL DETALLE HISTORIAL (Idéntico al de Historial) --}}
    <div id="modal-detalle-historial" class="fixed inset-0 z-[120] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="cerrarDetalleHistorial()"></div>

        <div class="fixed inset-x-0 bottom-0 bg-white rounded-t-[30px] shadow-2xl transform transition-transform duration-300 overflow-hidden max-w-md mx-auto">
            <div class="flex justify-center pt-3" onclick="cerrarDetalleHistorial()">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
            </div>

            <div class="p-6 pb-10">
                {{-- Encabezado --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <div class="flex items-center flex-wrap gap-2 mb-1">
                            <h3 id="md-titulo" class="text-2xl font-black text-gray-800 uppercase tracking-tight">---</h3>
                            {{-- Contenedor dinámico para inyectar los múltiples badges --}}
                            <div id="md-badges-container" class="flex items-center flex-wrap gap-1"></div>
                        </div>
                        <p id="md-fecha" class="text-blue-600 font-medium text-sm"></p>
                    </div>
                    <button onclick="cerrarDetalleHistorial()" class="p-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Foto --}}
                <div class="mb-6 bg-gray-100 rounded-2xl overflow-hidden border border-gray-100 shadow-inner min-h-[14rem]">
                    <img id="md-img" src="" class="w-full h-56 object-cover" onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=Sin+Evidencia';" />
                </div>

                {{-- Info Ubicación --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center mb-4 pb-4 border-b border-gray-100">
                        <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sucursal Asignada</p>
                            <p id="md-sucursal" class="text-sm font-bold text-gray-900">---</p>
                        </div>
                    </div>
                    
                    {{-- Mapa --}}
                    <div id="md-mapa" class="w-full h-40 rounded-xl overflow-hidden bg-gray-100 relative z-0"></div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    {{-- MODIFICACIÓN 2: Modal de Información de Sucursal (Fuera del flujo principal) --}}
    {{-- MODAL DE INFORMACIÓN DE SUCURSAL Y TURNOS --}}
    <div id="modal-sucursal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop oscuro --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="toggleModal('modal-sucursal')"></div>

        {{-- Contenedor del Modal --}}
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xs w-full max-w-sm">
                    
                    {{-- Cabecera Modal --}}
                    <div class="bg-blue-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                        <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">Información Laboral</h3>
                        <button onclick="toggleModal('modal-sucursal')" class="text-white hover:text-gray-200 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Cuerpo del Modal --}}
                    {{-- Cuerpo del Modal COMPACTO --}}
                    {{-- ... (código anterior del modal) ... --}}

{{-- Cuerpo del Modal COMPACTO --}}
<div class="px-4 py-4 space-y-4">
    
    {{-- Icono y Nombre Sucursal --}}
    <div class="text-center">
        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 mb-2">
            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <h4 class="text-base font-bold text-gray-900 leading-tight">
            {{ Auth::user()->empleado->sucursal->nombre ?? 'Sin Sucursal' }}
        </h4>
        <p class="text-xs text-gray-500">
            {{ Auth::user()->empleado->sucursal->direccion ?? '' }}
        </p>
    </div>
    
    {{-- CAJA CONTENEDORA --}}
    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">

        {{-- ======================================================= --}}
        {{-- NUEVA SECCIÓN: PERMISOS VIGENTES --}}
        {{-- ======================================================= --}}
        @php
            // Consultamos los permisos del empleado que aún no han vencido (fecha_fin >= hoy)
            // Asumiendo que la relación en el modelo Empleado es 'permisos'
            $misPermisos = Auth::user()->empleado->permisos()
                ->where('estado', 1)
                ->whereDate('fecha_fin', '>=', \Carbon\Carbon::today())
                ->with('tipoPermiso') // Cargamos el tipo para mostrar el nombre
                ->orderBy('fecha_inicio', 'asc')
                ->get();
        @endphp

        @if($misPermisos->isNotEmpty())
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-orange-700 font-bold text-[10px] uppercase tracking-wide">
                        <i class="fa-solid fa-file-contract mr-1"></i> Permisos Activos
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($misPermisos as $permiso)
                        @php
                             $esHoy = \Carbon\Carbon::now()->between($permiso->fecha_inicio, $permiso->fecha_fin);
                        @endphp
                        <div class="bg-white border-l-4 {{ $esHoy ? 'border-orange-500 shadow-sm' : 'border-orange-200 opacity-80' }} rounded-r px-3 py-2 border-y border-r border-gray-200">
                            
                            {{-- Título y Badge --}}
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-bold text-gray-800 leading-tight">
                                    {{ $permiso->tipoPermiso->nombre ?? 'Permiso' }}
                                </span>
                                @if($esHoy)
                                    <span class="text-[8px] bg-orange-100 text-orange-700 font-bold px-1.5 py-0.5 rounded-full animate-pulse">
                                        ACTIVO HOY
                                    </span>
                                @endif
                            </div>

                            {{-- Fechas --}}
                            <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                <i class="fa-regular fa-calendar text-gray-400"></i>
                                <span>
                                    {{ \Carbon\Carbon::parse($permiso->fecha_inicio)->format('d M') }} 
                                    - 
                                    {{ \Carbon\Carbon::parse($permiso->fecha_fin)->format('d M') }}
                                </span>
                            </div>
                            
                            <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                
                                <span>
                                    @if($permiso->valor !== null)
                                        <i class="fa-regular fa-clock text-gray-400"></i>
                                        {{ $permiso->valor ? 'Valor: ' . $permiso->valor .' mins.' : 'Sin valor asignado' }} 
                                    @elseif($permiso->id_tipo_permiso == 1)
                                        @if($permiso->cantidad_mts != null)
                                            <i class="fa-solid fa-arrows-left-right text-gray-400"></i>
                                            {{ 'Cantidad: ' . $permiso->cantidad_mts . ' mts.' }}
                                        @else
                                            <i class="fa-solid fa-arrows-left-right text-gray-400"></i>
                                            Es posible marcar en cualquier ubicación   
                                        @endif   
                                    @endif
                                </span>
                            </div>
                            {{-- Motivo (Opcional, cortado si es muy largo) --}}
                            @if($permiso->motivo)
                                <p class="text-[9px] text-gray-400 mt-1 italic truncate">
                                    {{ $permiso->motivo }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
                {{-- Separador visual --}}
                <div class="border-t border-gray-200 mt-4 border-dashed"></div>
            </div>
        @endif
        {{-- ======================================================= --}}


        {{-- 1. SECCIÓN: MIS TURNOS (Código existente) --}}
        <div class="mb-3 mt-2">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-700 font-bold text-[10px] uppercase tracking-wide">
                    <i class="fa-solid fa-user-clock mr-1"></i> Mis Turnos
                </span>
            </div>
            
            {{-- GRID: 1 col en móvil, 2 en pantallas más grandes --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @forelse(Auth::user()->empleado->horarios->sortBy('hora_ini') as $miHorario)
                    {{-- ... (Tu código de horarios existente se mantiene IGUAL AQUÍ) ... --}}
                    @php
                        // --- LÓGICA PARA DETECTAR TURNO ACTUAL ---
                        $esTurnoActual = false;
                        $now = \Carbon\Carbon::now();
                        $hoySlug = \Str::slug($now->locale('es')->isoFormat('ddd')); 
                        
                        $esDiaCorrecto = collect($miHorario->dias)->contains(function($d) use ($hoySlug) {
                            $diaDbSlug = \Str::slug(mb_substr($d, 0, 3));
                            return $diaDbSlug === $hoySlug;
                        });

                        if ($esDiaCorrecto) {
                            $inicio = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $miHorario->hora_ini);
                            $fin = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $miHorario->hora_fin);
                            if ($fin->lessThan($inicio)) { $fin->addDay(); }
                            if ($now->between($inicio->copy()->subMinutes(30), $fin->copy()->addMinutes(15))) {
                                $esTurnoActual = true;
                            }
                        }
                    @endphp

                    <div class="border-l-4 rounded px-2 py-1.5 shadow-sm relative transition-all duration-500
                        {{ $esTurnoActual ? 'bg-green-50 border-green-500 border border-green-200 animate-pulse' : 'bg-white border-blue-500 border-blue-200' }}">
                        
                        @if($esTurnoActual)
                            <div class="absolute top-1 right-1">
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                            </div>
                        @endif

                        <div class="text-xs font-black mb-1 {{ $esTurnoActual ? 'text-green-800' : 'text-gray-800' }}">
                            {{ \Carbon\Carbon::parse($miHorario->hora_ini)->format('H:i') }} - {{ \Carbon\Carbon::parse($miHorario->hora_fin)->format('H:i') }}
                        </div>
                        
                        <div class="flex flex-wrap gap-0.5">
                            @foreach($miHorario->dias ?? [] as $dia)
                                <span class="text-[8px] px-1 rounded capitalize leading-tight border
                                    {{ $esTurnoActual ? 'bg-green-100 text-green-700 border-green-200 font-bold' : 'bg-blue-50 text-blue-700 border-blue-100' }}">
                                    {{ mb_substr($dia, 0, 3) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-2 border border-dashed border-gray-200 rounded bg-white">
                        <span class="text-gray-400 italic text-xs">Sin asignación personal</span>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="border-t border-gray-200 my-2"></div>

        {{-- 2. SECCIÓN: HORARIOS SUCURSAL (Código existente se mantiene) --}}
        <div class="mb-2">
            <span class="text-gray-500 font-medium text-[10px] uppercase block mb-2">Atención General:</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @forelse(Auth::user()->empleado->sucursal->horarios as $h)
                    <div class="bg-white border border-gray-200 rounded px-2 py-1.5 shadow-sm opacity-80">
                        <div class="text-xs font-bold text-gray-600 mb-1">
                            {{ \Carbon\Carbon::parse($h->hora_ini)->format('H:i') }} - {{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}
                        </div>
                        <div class="flex flex-wrap gap-0.5">
                            @foreach($h->dias ?? [] as $dia)
                                <span class="text-[8px] bg-gray-100 text-gray-500 border border-gray-200 px-1 rounded capitalize leading-tight">
                                    {{ mb_substr($dia, 0, 3) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <span class="text-gray-400 italic text-xs col-span-full">No definido</span>
                @endforelse
            </div>
        </div>

        {{-- 3. TELÉFONO COMPACTO --}}
        <div class="mt-3 bg-white border border-gray-200 rounded p-2 flex justify-between items-center">
            <span class="text-gray-500 text-xs">Teléfono:</span>
            <span class="font-bold text-gray-800 text-xs flex items-center">
                <i class="fa-solid fa-phone mr-1.5 text-gray-400"></i>
                {{ Auth::user()->empleado->sucursal->telefono ?? 'N/A' }}
            </span>
        </div>
    </div>
</div>

                    {{-- Footer Modal --}}
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="toggleModal('modal-sucursal')">
                            Cerrar
                        </button>
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
    <script src="{{ asset('js/marcacion_app.js') }}"></script>
@endpush
</x-app-layout>