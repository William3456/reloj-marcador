<x-app-layout title="Historial de Asistencia">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Historial de Marcaciones
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">

                {{-- SECCIÓN 1: FILTROS --}}
                <form action="{{ route('marcaciones.index') }}" method="GET" class="mb-6 border-b border-gray-100 pb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Desde</label>
                            <input type="date" name="desde" value="{{ request('desde') ?? date('Y-m-d') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasta</label>
                            <input type="date" name="hasta" value="{{ request('hasta') ?? date('Y-m-d') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal</label>
                            <select name="sucursal"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todas</option>
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>
                                        {{ $suc->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empleado</label>
                            <select name="empleado"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todos</option>
                                @foreach($empleadosList as $emp)
                                    <option value="{{ $emp->id }}" {{ request('empleado') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->nombres }} {{ $emp->apellidos }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm flex items-center justify-center transition-colors">
                                <i class="fa-solid fa-filter mr-2"></i> Filtrar
                            </button>
                            <a href="{{ route('marcaciones.index') }}"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded shadow-sm text-sm flex items-center justify-center transition-colors"
                                title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>

                
                {{-- SECCIÓN 2: GRID AGRUPADO (EMPLEADO -> FECHA -> TURNOS) --}}
                <div class="space-y-8">
                    @forelse ($datosAgrupados as $empData)
                        @php
                            $emp = $empData['empleado'];
                            $iniciales = mb_substr($emp->nombres, 0, 1) . mb_substr($emp->apellidos, 0, 1);
                        @endphp

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            
                            {{-- Cabecera del Empleado --}}
                            <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-black text-lg border-2 border-white shadow-sm">
                                        {{ $iniciales }}
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-black text-gray-900 leading-tight">{{ $emp->nombres }} {{ $emp->apellidos }}</h2>
                                        <div class="flex items-center text-xs text-gray-500 mt-1">
                                            <i class="fa-solid fa-briefcase mr-1.5"></i> {{ $emp->puesto->nombre ?? 'Sin Puesto' }}
                                            <span class="mx-2">•</span>
                                            <i class="fa-solid fa-store mr-1.5"></i> {{ $emp->sucursal->nombre ?? 'Sin Sucursal' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Días y Turnos --}}
                            <div class="p-6 space-y-8">
                                @foreach ($empData['fechas'] as $fechaStr => $fechaData)
                                    <div>
                                        {{-- Encabezado de la Fecha --}}
                                        <h3 class="text-sm font-bold text-gray-700 mb-4 border-b border-gray-100 pb-2 uppercase tracking-wide flex items-center gap-2">
                                            <i class="fa-regular fa-calendar-days text-blue-500"></i>
                                            {{ $fechaData['fecha_obj']->locale('es')->isoFormat('dddd, D [de] MMMM YYYY') }}
                                        </h3>

                                        {{-- Grid de Turnos de ese Día --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                            @foreach ($fechaData['turnos'] as $t)
                                                @php
                                                    $m = $t->marcacion;
                                                    $h = $t->horario;
                                                    
                                                    $permisosE = []; $permisosS = [];
                                                    $horaSalidaStr = '--:--';
                                                    
                                                    // Banderas para el Modal
                                                    $esEntradaTarde = false;
                                                    $esOlvidoSalida = false;
                                                    $salidaReal = null;
                                                    $esDiaDiferente = false;
                                                    
                                                    if ($m) {
                                                        $esEntradaTarde = $m->fuera_horario ? true : false;
                                                        $permisosE = $m->permisos->map(fn($p) => ['nombre' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo])->toArray();
                                                        
                                                        if ($m->salida) {
                                                            $salidaReal = $m->salida->created_at;
                                                            $esDiaDiferente = $m->created_at->format('Y-m-d') !== $salidaReal->format('Y-m-d');
                                                            $esOlvidoSalida = $m->salida->es_olvido || $esDiaDiferente;
                                                            
                                                            if ($h && !$esOlvidoSalida) {
                                                                $finTurno = \Carbon\Carbon::parse($fechaData['fecha_obj']->format('Y-m-d') . ' ' . $h->hora_fin);
                                                                if ($h->hora_fin < $h->hora_ini) $finTurno->addDay();
                                                                
                                                                if ($salidaReal->gt($finTurno) && $salidaReal->diffInMinutes($finTurno) > 60) {
                                                                    $esOlvidoSalida = true;
                                                                }
                                                            }

                                                            if ($esDiaDiferente) {
                                                                $horaSalidaStr = $salidaReal->format('h:i A') . ' (' . $salidaReal->format('d/m') . ')';
                                                            } else {
                                                                $horaSalidaStr = $salidaReal->format('h:i A');
                                                            }
                                                            
                                                            $permisosS = $m->salida->permisos->map(fn($p) => ['nombre' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo])->toArray();
                                                        }
                                                    }
                                                @endphp

                                                <div class="bg-white rounded-xl border border-gray-200 shadow-sm relative flex flex-col overflow-hidden group hover:border-blue-300 transition-colors">
                                                    <div class="h-1.5 w-full {{ $t->estado->borde }}"></div>
                                                    
                                                    <div class="p-4 flex-grow">
                                                        <div class="flex justify-between items-center mb-3">
                                                            <span class="text-xs font-bold text-gray-600 flex items-center gap-1.5 bg-gray-100 px-2 py-1 rounded">
                                                                <i class="fa-regular fa-clock"></i>
                                                                {{ $h ? \Carbon\Carbon::parse($h->hora_ini)->format('H:i') . ' - ' . \Carbon\Carbon::parse($h->hora_fin)->format('H:i') : 'Turno Extra' }}
                                                            </span>
                                                            <span class="inline-block px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider border {{ $t->estado->clase }}">
                                                                {{ $t->estado->texto }}
                                                            </span>
                                                        </div>

                                                        <div class="grid grid-cols-2 gap-2 mt-4">
                                                            <div class="pr-2 border-r border-gray-100">
                                                                <p class="text-[9px] font-bold text-gray-400 uppercase mb-0.5">Entrada</p>
                                                                @if($m)
                                                                    <div class="flex items-end gap-1">
                                                                        <p class="font-black text-gray-800 text-lg leading-none">{{ $m->created_at->format('H:i') }}</p>
                                                                        @if($esEntradaTarde) <span class="text-[8px] text-orange-500 font-bold mb-0.5">TARDE</span> @endif
                                                                    </div>
                                                                @else
                                                                    <p class="font-bold text-gray-300 text-lg leading-none">--:--</p>
                                                                @endif
                                                            </div>
                                                            <div class="pl-2">
                                                                <p class="text-[9px] font-bold text-gray-400 uppercase mb-0.5">Salida</p>
                                                                @if($m && $m->salida)
                                                                    <div class="flex items-end gap-1">
                                                                        <p class="font-black text-gray-800 text-lg leading-none">{{ $salidaReal->format('H:i') }}</p>
                                                                        @if($esOlvidoSalida)
                                                                            <span class="text-[8px] text-red-500 font-bold mb-0.5">OLVIDO</span>
                                                                        @endif
                                                                    </div>
                                                                    @if($esDiaDiferente)
                                                                        <p class="text-[9px] text-red-500 font-bold mt-1 truncate" title="Marcó salida en un día distinto">
                                                                            <i class="fa-regular fa-calendar mr-0.5"></i> {{ $salidaReal->format('d M') }}
                                                                        </p>
                                                                    @endif
                                                                @else
                                                                    <p class="font-bold text-gray-300 text-lg leading-none">--:--</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if($m)
                                                        <button onclick='verDetalleCompleto({
                                                            empleado: @json($emp->nombres . " " . $emp->apellidos),
                                                            fecha: @json($m->created_at->isoFormat("dddd D [de] MMMM [del] YYYY")),
                                                            latEntrada: {{ (float)($m->latitud ?? 0) }},
                                                            lngEntrada: {{ (float)($m->longitud ?? 0) }},
                                                            latSalida: {{ (float)(optional($m->salida)->latitud ?? 0) }},
                                                            lngSalida: {{ (float)(optional($m->salida)->longitud ?? 0) }},
                                                            fotoEntrada: @json($m->ubi_foto ? Storage::url($m->ubi_foto) : null),
                                                            horaEntrada: @json($m->created_at->format("h:i A")),
                                                            hasSalida: {{ $m->salida ? 'true' : 'false' }},
                                                            fotoSalida: @json(($m->salida && $m->salida->ubi_foto) ? Storage::url($m->salida->ubi_foto) : null),
                                                            horaSalida: @json($horaSalidaStr),
                                                            esEntradaTarde: @json($esEntradaTarde), {{-- NUEVO --}}
                                                            esOlvidoSalida: @json($esOlvidoSalida), {{-- NUEVO --}}
                                                            estadoTexto: @json($t->estado->texto),
                                                            estadoClase: @json($t->estado->clase),
                                                            fotoEntradaFull: @json($m->ubi_foto_full ? Storage::url($m->ubi_foto_full) : null),
                                                            fotoSalidaFull: @json(($m->salida && $m->salida->ubi_foto_full) ? Storage::url($m->salida->ubi_foto_full) : null),
                                                            permisosEntrada: @json($permisosE),
                                                            permisosSalida: @json($permisosS)
                                                        })' class="w-full bg-blue-50/50 hover:bg-blue-600 text-blue-600 hover:text-white py-2 px-4 text-[10px] uppercase tracking-wider font-bold transition-colors flex items-center justify-center gap-1.5 border-t border-gray-100">
                                                            <i class="fa-solid fa-expand"></i> Detalles
                                                        </button>
                                                    @else
                                                        @if(str_contains($t->estado->clase, 'blue'))
                                                            <div class="w-full bg-blue-50/50 text-blue-600 py-2 px-4 text-[10px] uppercase tracking-wider font-bold text-center border-t border-blue-100">
                                                                <i class="fa-solid fa-file-shield mr-1"></i> Día Exonerado
                                                            </div>
                                                        @else
                                                            <div class="w-full bg-gray-50/50 text-gray-400 py-2 px-4 text-[10px] uppercase tracking-wider font-bold text-center border-t border-gray-100">
                                                                <i class="fa-solid fa-ban mr-1"></i> Sin Registros
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-20 px-4 text-center bg-white rounded-3xl border border-gray-200 shadow-sm">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 border border-gray-100 mb-4">
                                <i class="fa-solid fa-clipboard-user text-3xl text-gray-300"></i>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Cero resultados</h3>
                            <p class="text-sm text-gray-500 max-w-md mx-auto">No se encontraron empleados con horarios asignados o marcaciones para los filtros seleccionados.</p>
                        </div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL EXPEDIENTE --}}
    <div id="modalExpediente" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-80 transition-opacity backdrop-blur-sm"
            onclick="cerrarModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-gray-200">

                    {{-- Cabecera Modal --}}
                    <div
                        class="bg-gray-50 px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-gray-900 leading-6" id="modalEmpNombre">---
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-500" id="modalFecha">---</p>
                            <span id="modalBadgeStatus"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border mt-1">---</span>
                        </div>
                        <button onclick="cerrarModal()"
                            class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 p-2 rounded-full border border-gray-200 transition">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="px-4 sm:px-6 py-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                            {{-- Columna 1: Fotos --}}
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mb-2">
                                    Evidencia Fotográfica</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Foto Entrada --}}
                                    <div
                                        class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center relative flex flex-col">
                                        <span class="text-xs font-bold text-green-700 block mb-1">ENTRADA</span>
                                        <input type="hidden" id="fotoEntradaFull">
                                        <input type="hidden" id="fotoSalidaFull">

                                        <div
                                            class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group mb-1">
                                            <img id="imgEntrada" src=""
                                                class="w-full h-full object-cover hidden cursor-pointer"
                                                onclick="zoomImagen(document.getElementById('fotoEntradaFull').value)">
                                            <span id="noImgEntrada" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <div class="flex items-center justify-center gap-1.5 mt-1 mb-1">
                                            <p id="horaEntradaModal" class="text-xs text-gray-600 font-mono font-bold">--:--</p>
                                            <span id="badgeEntradaTarde" class="hidden text-[9px] bg-orange-100 text-orange-600 font-black px-1.5 py-0.5 rounded shadow-sm">TARDE</span>
                                        </div>

                                        {{-- Contenedor dinámico de permisos para ENTRADA --}}
                                        <div id="contenedorPermisosEntrada" class="w-full flex flex-col gap-1 mt-1">
                                        </div>
                                    </div>

                                    {{-- Foto Salida --}}
                                    <div
                                        class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center relative flex flex-col">
                                        <span class="text-xs font-bold text-red-700 block mb-1">SALIDA</span>
                                        <div
                                            class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group mb-1">
                                            <img id="imgSalida" src=""
                                                class="w-full h-full object-cover hidden cursor-pointer"
                                                onclick="zoomImagen(document.getElementById('fotoSalidaFull').value)">
                                            <span id="noImgSalida" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <div class="flex items-center justify-center gap-1.5 mt-1 mb-1">
                                            <p id="horaSalidaModal" class="text-xs text-gray-600 font-mono font-bold">--:--</p>
                                            <span id="badgeSalidaOlvido" class="hidden text-[9px] bg-red-100 text-red-600 font-black px-1.5 py-0.5 rounded shadow-sm">OLVIDO</span>
                                        </div>

                                        {{-- Contenedor dinámico de permisos para SALIDA --}}
                                        <div id="contenedorPermisosSalida" class="w-full flex flex-col gap-1 mt-1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna 2: Mapa --}}
                            <div class="flex flex-col h-full">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mb-2">
                                    Ubicación</h4>
                                <div class="flex gap-2 mb-3" id="mapToggles">
                                    <button onclick="cambiarMapa('entrada')" id="btnMapEntrada"
                                        class="flex-1 text-xs py-1.5 rounded-md font-bold bg-green-100 text-green-800 border border-green-200 transition">Ver
                                        Entrada</button>
                                    <button onclick="cambiarMapa('salida')" id="btnMapSalida"
                                        class="flex-1 text-xs py-1.5 rounded-md font-bold bg-white text-gray-500 border border-gray-200 transition">Ver
                                        Salida</button>
                                </div>
                                <div
                                    class="flex-grow bg-gray-100 rounded-xl overflow-hidden border border-gray-300 relative min-h-[250px]">
                                    <div id="mapaExpediente" class="w-full h-full absolute inset-0"></div>
                                </div>
                                <div
                                    class="mt-3 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-start gap-3">
                                    <i class="fa-solid fa-map-location-dot text-blue-500 mt-0.5"></i>
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-400 uppercase">Dirección Detectada
                                            (API)</p>
                                        <p id="txtDireccion" class="text-xs text-blue-900 font-medium leading-snug">
                                            Cargando...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let map, marker, geocoder;
            let currentData = {};

            function initMap() {
                if (typeof google === 'undefined') return;
                geocoder = new google.maps.Geocoder();
                map = new google.maps.Map(document.getElementById("mapaExpediente"), {
                    zoom: 16,
                    center: { lat: 13.69, lng: -89.24 },
                    disableDefaultUI: true,
                    zoomControl: true
                });
                marker = new google.maps.Marker({ map: map });
            }

            function verDetalleCompleto(data) {
                currentData = data;
                document.getElementById('modalEmpNombre').innerText = data.empleado;
                document.getElementById('modalFecha').innerText = data.fecha;
                document.getElementById('horaEntradaModal').innerText = data.horaEntrada;
                document.getElementById('horaSalidaModal').innerText = data.horaSalida;
                document.getElementById('fotoEntradaFull').value = data.fotoEntradaFull;
                document.getElementById('fotoSalidaFull').value = data.fotoSalidaFull;

                const badge = document.getElementById('modalBadgeStatus');
                badge.innerText = data.estadoTexto;
                badge.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border " + data.estadoClase;

                setupFoto('imgEntrada', 'noImgEntrada', data.fotoEntrada);
                setupFoto('imgSalida', 'noImgSalida', data.fotoSalida);

                // --- ITERAR PERMISOS EN EL MODAL ---

                // 1. Permisos Entrada
                const contP_Entrada = document.getElementById('contenedorPermisosEntrada');
                contP_Entrada.innerHTML = ''; // Limpiar el contenedor

                if (data.permisosEntrada && data.permisosEntrada.length > 0) {
                    data.permisosEntrada.forEach(p => {
                        let html = `<div class="w-full"><span class="block w-full bg-purple-100 text-purple-800 text-[9px] font-bold px-2 py-1.5 rounded-md border border-purple-200 shadow-sm whitespace-normal leading-tight">${p.nombre}`;
                        if (p.motivo) html += `<br><span class="font-normal italic opacity-80">${p.motivo}</span>`;
                        html += `</span></div>`;
                        contP_Entrada.innerHTML += html;
                    });
                }

                // 2. Permisos Salida
                const contP_Salida = document.getElementById('contenedorPermisosSalida');
                contP_Salida.innerHTML = ''; // Limpiar el contenedor

                if (data.permisosSalida && data.permisosSalida.length > 0) {
                    data.permisosSalida.forEach(p => {
                        let html = `<div class="w-full"><span class="block w-full bg-purple-100 text-purple-800 text-[9px] font-bold px-2 py-1.5 rounded-md border border-purple-200 shadow-sm whitespace-normal leading-tight">${p.nombre}`;
                        if (p.motivo) html += `<br><span class="font-normal italic opacity-80">${p.motivo}</span>`;
                        html += `</span></div>`;
                        contP_Salida.innerHTML += html;
                    });
                }

                // --- MAPA Y VISTA ---
                const btnSalida = document.getElementById('btnMapSalida');
                if (!data.hasSalida) {
                    btnSalida.classList.add('hidden');
                } else {
                    btnSalida.classList.remove('hidden');
                }

                document.getElementById('modalExpediente').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                // Toggle Badges de Observaciones
                if (data.esEntradaTarde) {
                    document.getElementById('badgeEntradaTarde').classList.remove('hidden');
                } else {
                    document.getElementById('badgeEntradaTarde').classList.add('hidden');
                }

                if (data.esOlvidoSalida) {
                    document.getElementById('badgeSalidaOlvido').classList.remove('hidden');
                } else {
                    document.getElementById('badgeSalidaOlvido').classList.add('hidden');
                }
                if (!map) initMap();
                setTimeout(() => { cambiarMapa('entrada'); }, 200);
            }

            function setupFoto(imgId, placeholderId, url) {
                const img = document.getElementById(imgId);
                const placeholder = document.getElementById(placeholderId);
                if (url) {
                    img.src = url;
                    img.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                } else {
                    img.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                }
            }

            function cambiarMapa(tipo) {
                if (!map) return;
                let lat, lng;
                const btnEntrada = document.getElementById('btnMapEntrada');
                const btnSalida = document.getElementById('btnMapSalida');

                if (tipo === 'entrada') {
                    lat = currentData.latEntrada; lng = currentData.lngEntrada;
                    btnEntrada.className = "flex-1 text-xs py-1.5 rounded-md font-bold bg-green-100 text-green-800 border border-green-200 transition shadow-inner";
                    btnSalida.className = "flex-1 text-xs py-1.5 rounded-md font-bold bg-white text-gray-500 border border-gray-200 hover:bg-gray-50 transition";
                } else {
                    lat = currentData.latSalida; lng = currentData.lngSalida;
                    btnEntrada.className = "flex-1 text-xs py-1.5 rounded-md font-bold bg-white text-gray-500 border border-gray-200 hover:bg-gray-50 transition";
                    btnSalida.className = "flex-1 text-xs py-1.5 rounded-md font-bold bg-red-100 text-red-800 border border-red-200 transition shadow-inner";
                }

                const pos = { lat: lat, lng: lng };
                map.setCenter(pos);
                map.setZoom(16);
                marker.setPosition(pos);

                document.getElementById('txtDireccion').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando satélite...';
                geocoder.geocode({ location: pos }, (results, status) => {
                    if (status === "OK" && results[0]) {
                        document.getElementById('txtDireccion').innerText = results[0].formatted_address;
                    } else {
                        document.getElementById('txtDireccion').innerText = "Coordenadas: " + lat + ", " + lng;
                    }
                });
            }

            function cerrarModal() {
                document.getElementById('modalExpediente').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            function zoomImagen(src) { if (src) window.open(src, '_blank'); }
            document.addEventListener('keydown', function (event) { if (event.key === "Escape") cerrarModal(); });
        </script>
    @endpush
</x-app-layout>