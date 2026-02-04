<x-app-layout title="Historial de Asistencia">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reporte de Asistencia') }}
        </h2>
    </x-slot>

    {{-- CONTENEDOR PADRE --}}
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-100 shadow rounded-lg p-4 sm:p-6">
                
                {{-- BLOQUE 1: FILTROS (RESPONSIVE) --}}
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-8 border border-gray-200">
                    
                    <h3 class="text-gray-700 font-bold text-sm mb-4">
                        <i class="fa-solid fa-sliders mr-2"></i>Opciones de Filtrado
                    </h3>

                    <form action="{{ route('marcaciones.index') }}" method="GET">
                        {{-- Usamos GRID para que se adapte: 1 columna en móvil, 2 en tablet, auto en escritorio --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-4 items-end">
                            
                            {{-- Input Desde --}}
                            <div class="w-full lg:w-auto">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Desde</label>
                                <input type="date" name="desde" value="{{ request('desde') ?? date('Y-m-01') }}"
                                    class="w-full lg:w-36 text-sm border-gray-300 rounded-md focus:ring-blue-500 h-9 shadow-sm text-gray-700">
                            </div>

                            {{-- Input Hasta --}}
                            <div class="w-full lg:w-auto">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Hasta</label>
                                <input type="date" name="hasta" value="{{ request('hasta') ?? date('Y-m-d') }}"
                                    class="w-full lg:w-36 text-sm border-gray-300 rounded-md focus:ring-blue-500 h-9 shadow-sm text-gray-700">
                            </div>

                            {{-- GRUPO DE ACCIONES (Botones) --}}
                            {{-- En móvil ocupan ancho completo o grid de 2 --}}
                            <div class="col-span-1 sm:col-span-2 lg:col-span-auto flex flex-wrap gap-2 w-full lg:w-auto">
                                
                                {{-- 1. Botón Filtrar --}}
                                <button type="submit"
                                    class="flex-1 lg:flex-none justify-center bg-blue-600 text-white px-4 py-2 rounded-md text-xs font-bold hover:bg-blue-700 h-9 shadow-md transition-colors flex items-center gap-2 min-w-[90px]"
                                    title="Aplicar rango">
                                    <i class="fa-solid fa-filter"></i> Filtrar
                                </button>

                                {{-- 2. Botón Turno Actual --}}
                                <a href="{{ route('marcaciones.index', ['desde' => date('Y-m-d'), 'hasta' => date('Y-m-d')]) }}"
                                   class="flex-1 lg:flex-none justify-center bg-green-600 text-white px-4 py-2 rounded-md text-xs font-bold hover:bg-green-700 h-9 shadow-md transition-colors flex items-center gap-2 min-w-[110px]"
                                   title="Ver hoy">
                                    <i class="fa-regular fa-calendar-check"></i> Hoy
                                </a>

                                {{-- 3. Botón En Proceso --}}
                                <a href="{{ route('marcaciones.index', [
                                        'desde' => request('desde') ?? date('Y-m-01'), 
                                        'hasta' => request('hasta') ?? date('Y-m-d'),
                                        'estado' => 'sin_cierre'
                                    ]) }}"
                                   class="flex-1 lg:flex-none justify-center bg-yellow-500 text-white px-4 py-2 rounded-md text-xs font-bold hover:bg-yellow-600 h-9 shadow-md transition-colors flex items-center gap-2 min-w-[130px]"
                                   title="Sin cierre">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Pendientes
                                </a>

                                {{-- 4. Botón Limpiar --}}
                                <a href="{{ route('marcaciones.index') }}"
                                    class="flex-none bg-white text-gray-600 border border-gray-300 px-4 py-2 rounded-md text-xs font-bold hover:bg-gray-100 h-9 shadow-sm flex items-center gap-2 transition-colors"
                                    title="Limpiar filtros">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- BLOQUE 2: TABLA (RESPONSIVE CON SCROLL) --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    {{-- IMPORTANTE: overflow-x-auto permite scroll horizontal en móviles --}}
                    <div class="overflow-x-auto">
                        <table id="tablaMarcaciones" class="w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600">
                                    {{-- Usamos min-w-[px] para asegurar que la columna no se aplaste --}}
                                    <th class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider min-w-[200px]">
                                        Empleado
                                    </th>
                                    <th class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider min-w-[120px]">
                                        Sucursal
                                    </th>
                                    <th class="px-2 py-3 text-center text-[10px] font-extrabold uppercase tracking-wider bg-green-50 border-b border-green-200 text-green-800 min-w-[140px]">
                                        Entrada
                                    </th>
                                    <th class="px-2 py-3 text-center text-[10px] font-extrabold uppercase tracking-wider bg-red-50 border-b border-red-200 text-red-800 min-w-[140px]">
                                        Salida
                                    </th>
                                    <th class="px-2 py-3 text-center text-[10px] font-extrabold uppercase tracking-wider min-w-[110px]">
                                        Estado
                                    </th>
                                    <th class="px-2 py-3 text-center text-[10px] font-extrabold uppercase tracking-wider min-w-[60px]">
                                        Ver
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100 text-xs">
                                @foreach ($marcaciones as $m)
                                    <tr class="hover:bg-blue-50 transition-colors cursor-default group">

                                        {{-- Empleado --}}
                                        <td class="px-4 py-3 border-r border-gray-50">
                                            <div class="font-bold text-gray-900 truncate max-w-[200px]"
                                                title="{{ $m->empleado->nombres }} {{ $m->empleado->apellidos }}">
                                                {{ $m->empleado->nombres }} {{ $m->empleado->apellidos }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                                {{ $m->empleado->cod_trabajador }}
                                            </div>
                                        </td>

                                        {{-- Sucursal --}}
                                        <td class="px-4 py-3 border-r border-gray-50">
                                            <div class="flex items-center gap-1.5 text-gray-600">
                                                <i class="fa-solid fa-store text-gray-300 text-[10px]"></i>
                                                <span class="truncate max-w-[120px]"
                                                    title="{{ $m->sucursal->nombre ?? 'GPS' }}">{{ $m->sucursal->nombre ?? 'GPS' }}</span>
                                            </div>
                                        </td>

                                        {{-- Entrada --}}
                                        <td class="px-2 py-3 bg-green-50/20 border-r border-green-50 text-center">
                                            <div class="font-bold text-gray-800 text-[10px] capitalize leading-tight">
                                                {{ $m->created_at->isoFormat('D [de] MMMM [de] YYYY') }}
                                            </div>
                                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                {{ $m->created_at->format('h:i A') }}
                                            </div>

                                            @if($m->id_permiso_aplicado)
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold border border-blue-200 inline-block mt-1">
                                                    Con Permiso
                                                </span>
                                            @elseif($m->fuera_horario)
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-bold border border-orange-200 inline-block mt-1">
                                                    Tarde
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Salida --}}
                                        <td class="px-2 py-3 bg-red-50/20 border-r border-red-50 text-center">
                                            @if($m->salida)
                                                <div class="font-bold text-gray-800 text-[10px] capitalize leading-tight">
                                                    {{ $m->salida->created_at->isoFormat('D [de] MMMM [de] YYYY') }}
                                                </div>
                                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                    {{ $m->salida->created_at->format('h:i A') }}
                                                </div>

                                                @if($m->salida->es_olvido || $m->salida->fuera_horario)
                                                    <span class="text-[9px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold border border-red-200 inline-block mt-1">
                                                        Olvido
                                                    </span>
                                                @endif
                                            @else
                                                @if(!$m->created_at->isToday())
                                                    <span class="text-[9px] font-bold text-red-500 block mt-1">SIN CIERRE</span>
                                                @else
                                                    <span class="text-[9px] font-bold text-gray-300 block mt-1">--:--</span>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Estado --}}
                                        <td class="px-2 py-3 border-r border-gray-50 text-center">
                                            @if($m->salida)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[9px] font-bold bg-green-100 text-green-800 border border-green-200 whitespace-nowrap">
                                                    Completo
                                                </span>
                                            @else
                                                @if(!$m->created_at->isToday())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-800 border border-red-200 whitespace-nowrap">
                                                        Incompleto
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[9px] font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 animate-pulse whitespace-nowrap">
                                                        En Turno
                                                    </span>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Ver (Botón Modal) --}}
                                        <td class="px-2 py-3 text-center">
                                            @php
                                                $estadoTexto = '';
                                                $estadoClase = '';
                                                if ($m->salida) {
                                                    if ($m->salida->es_olvido || $m->salida->fuera_horario) {
                                                        $estadoTexto = 'Jornada Finalizada (Con Retraso/Olvido)';
                                                        $estadoClase = 'bg-orange-100 text-orange-800 border-orange-200';
                                                    } else {
                                                        $estadoTexto = 'Jornada Completada Exitosamente';
                                                        $estadoClase = 'bg-green-100 text-green-800 border-green-200';
                                                    }
                                                } else {
                                                    if (!$m->created_at->isToday()) {
                                                        $estadoTexto = 'Cierre Pendiente (Olvido de Salida)';
                                                        $estadoClase = 'bg-red-100 text-red-800 border-red-200 animate-pulse';
                                                    } else {
                                                        $estadoTexto = 'En Turno (Jornada Activa)';
                                                        $estadoClase = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                                    }
                                                }
                                            @endphp

                                            <button onclick="verDetalleCompleto({
                                                empleado: '{{ $m->empleado->nombres }} {{ $m->empleado->apellidos }}',
                                                fecha: '{{ $m->created_at->isoFormat('dddd D [de] MMMM [del] YYYY') }}',
                                                latEntrada: {{ $m->latitud }},
                                                lngEntrada: {{ $m->longitud }},
                                                fotoEntrada: '{{ $m->ubi_foto ? Storage::url($m->ubi_foto) : null }}',
                                                horaEntrada: '{{ $m->created_at->format('h:i A') }}',
                                                hasSalida: {{ $m->salida ? 'true' : 'false' }},
                                                latSalida: {{ $m->salida->latitud ?? 0 }},
                                                lngSalida: {{ $m->salida->longitud ?? 0 }},
                                                fotoSalida: '{{ ($m->salida && $m->salida->ubi_foto) ? Storage::url($m->salida->ubi_foto) : null }}',
                                                horaSalida: '{{ $m->salida ? $m->salida->created_at->format('h:i A') : '--' }}',
                                                estadoTexto: '{{ $estadoTexto }}',
                                                estadoClase: '{{ $estadoClase }}',
                                                fotoEntradaFull: '{{ $m->ubi_foto_full ? Storage::url($m->ubi_foto_full) : null }}',
                                                fotoSalidaFull: '{{ $m->ubi_foto_full ? Storage::url($m->ubi_foto_full) : null }}'
                                            })" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 w-8 h-8 rounded flex items-center justify-center transition mx-auto"
                                                title="Ver Detalle">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EXPEDIENTE --}}
    <div id="modalExpediente" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-80 transition-opacity backdrop-blur-sm" onclick="cerrarModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-gray-200">

                    {{-- Cabecera Modal --}}
                    <div class="bg-gray-50 px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-gray-900 leading-6" id="modalEmpNombre">---</h3>
                            <p class="text-xs sm:text-sm text-gray-500" id="modalFecha">---</p>
                            <span id="modalBadgeStatus" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border mt-1">---</span>
                        </div>
                        <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 p-2 rounded-full border border-gray-200 transition">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="px-4 sm:px-6 py-6">
                        {{-- Grid Modal Responsive --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                            
                            {{-- Columna 1: Fotos --}}
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mb-2">Evidencia Fotográfica</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Foto Entrada --}}
                                    <div class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center">
                                        <span class="text-xs font-bold text-green-700 block mb-1">ENTRADA</span>
                                        <input type="hidden" id="fotoEntradaFull">
                                        <input type="hidden" id="fotoSalidaFull">
                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group">
                                            <img id="imgEntrada" src="" class="w-full h-full object-cover hidden cursor-pointer" onclick="zoomImagen(document.getElementById('fotoEntradaFull').value)">
                                            <span id="noImgEntrada" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <p id="horaEntradaModal" class="text-xs text-gray-600 mt-1 font-mono font-bold">--:--</p>
                                    </div>
                                    {{-- Foto Salida --}}
                                    <div class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center">
                                        <span class="text-xs font-bold text-red-700 block mb-1">SALIDA</span>
                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group">
                                            <img id="imgSalida" src="" class="w-full h-full object-cover hidden cursor-pointer" onclick="zoomImagen(document.getElementById('fotoSalidaFull').value)">
                                            <span id="noImgSalida" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <p id="horaSalidaModal" class="text-xs text-gray-600 mt-1 font-mono font-bold">--:--</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna 2: Mapa --}}
                            <div class="flex flex-col h-full">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mb-2">Ubicación</h4>
                                <div class="flex gap-2 mb-3" id="mapToggles">
                                    <button onclick="cambiarMapa('entrada')" id="btnMapEntrada" class="flex-1 text-xs py-1.5 rounded-md font-bold bg-green-100 text-green-800 border border-green-200 transition">Ver Entrada</button>
                                    <button onclick="cambiarMapa('salida')" id="btnMapSalida" class="flex-1 text-xs py-1.5 rounded-md font-bold bg-white text-gray-500 border border-gray-200 transition">Ver Salida</button>
                                </div>
                                <div class="flex-grow bg-gray-100 rounded-xl overflow-hidden border border-gray-300 relative min-h-[250px]">
                                    <div id="mapaExpediente" class="w-full h-full absolute inset-0"></div>
                                </div>
                                <div class="mt-3 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-start gap-3">
                                    <i class="fa-solid fa-map-location-dot text-blue-500 mt-0.5"></i>
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-400 uppercase">Dirección Detectada (API)</p>
                                        <p id="txtDireccion" class="text-xs text-blue-900 font-medium leading-snug">Cargando...</p>
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
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script>
            // Configuración DataTables para que NO oculte columnas (usamos scroll propio)
            let table = new DataTable('#tablaMarcaciones', {
                responsive: false, // Desactivamos el responsive nativo de DataTables porque usamos scroll
                scrollX: true,     // Activamos scroll si es necesario por configuración
                paging: true,
                pageLength: 10,
                searching: true,
                info: true,
                ordering: true,
                order: [], // Sin orden inicial
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json',
                    search: "Buscar:",
                    lengthMenu: "_MENU_",
                },
                columnDefs: [{ orderable: false, targets: [5] }]
            });

            // ... (Resto de tu lógica JS del Mapa y Modal se mantiene igual) ...
            let map, marker, geocoder;
            let currentData = {};

            function initMap() {
                // Validación para evitar errores si Google Maps no cargó
                if(typeof google === 'undefined') return; 
                
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

                const btnSalida = document.getElementById('btnMapSalida');
                if (!data.hasSalida) {
                    btnSalida.classList.add('hidden');
                } else {
                    btnSalida.classList.remove('hidden');
                }

                document.getElementById('modalExpediente').classList.remove('hidden');
                document.body.style.overflow = 'hidden';

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
                if(!map) return;

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

            function zoomImagen(src) { if(src) window.open(src, '_blank'); }
            document.addEventListener('keydown', function (event) { if (event.key === "Escape") cerrarModal(); });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
        <style>
            /* Ajustes finos para DataTables en móvil */
            .dataTables_wrapper .dataTables_filter { margin-bottom: 10px; margin-top: 10px; }
            .dataTables_wrapper .dataTables_filter input { 
                border-radius: 0.5rem; 
                border-color: #d1d5db; 
                padding: 0.25rem 0.5rem; 
                font-size: 0.875rem; 
            }
            /* Quitar bordes feos predeterminados */
            table.dataTable.no-footer { border-bottom: 1px solid #e5e7eb; }
        </style>
    @endpush
</x-app-layout>