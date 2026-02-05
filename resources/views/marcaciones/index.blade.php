<x-app-layout title="Historial de Asistencia">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Historial de Marcaciones
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">

                {{-- SECCIÓN 1: FILTROS (Estilo Reporte) --}}
                <form action="{{ route('marcaciones.index') }}" method="GET" class="mb-6 border-b border-gray-100 pb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        
                        {{-- Rango Fechas --}}
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

                        {{-- Sucursal --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal</label>
                            <select name="sucursal"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todas</option>
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>
                                        {{ $suc->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Empleado --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empleado</label>
                            <select name="empleado"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todos</option>
                                @foreach($empleadosList as $emp)
                                    <option value="{{ $emp->id }}" {{ request('empleado') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->nombres }} {{ $emp->apellidos }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botones de Acción --}}
                        <div class="flex gap-2">
                            <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm flex items-center justify-center transition-colors">
                                <i class="fa-solid fa-filter mr-2"></i> Filtrar
                            </button>
                            {{-- Botón Reset --}}
                            <a href="{{ route('marcaciones.index') }}" 
                               class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded shadow-sm text-sm flex items-center justify-center transition-colors"
                               title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                    
                    {{-- Filtros Rápidos (Opcional) --}}
                    <div class="mt-4 flex gap-3 text-xs">
                        <span class="text-gray-400 font-bold uppercase self-center">Vistas Rápidas:</span>
                        <a href="{{ route('marcaciones.index', ['desde' => date('Y-m-d'), 'hasta' => date('Y-m-d')]) }}" 
                           class="px-3 py-1 bg-green-50 text-green-700 rounded-full border border-green-200 hover:bg-green-100 transition">
                           Hoy
                        </a>
                        <a href="{{ route('marcaciones.index', ['estado' => 'sin_cierre']) }}" 
                           class="px-3 py-1 bg-yellow-50 text-yellow-700 rounded-full border border-yellow-200 hover:bg-yellow-100 transition">
                           Pendientes de Cierre
                        </a>
                        <a href="{{ route('marcaciones.index', ['incidencia' => 'tarde']) }}" 
                           class="px-3 py-1 bg-orange-50 text-orange-700 rounded-full border border-orange-200 hover:bg-orange-100 transition">
                           Llegadas Tarde
                        </a>
                    </div>
                </form>

                {{-- SECCIÓN 2: TABLA DATA TABLES (Estilo Visual mejorado) --}}
                <div class="overflow-hidden">
                    <table id="tablaMarcaciones" class="min-w-full divide-y divide-gray-200 w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sucursal</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Entrada</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Salida</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($marcaciones as $m)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    {{-- Empleado --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ $m->empleado->nombres }} {{ $m->empleado->apellidos }}</div>
                                                <div class="text-xs text-gray-500 font-mono">{{ $m->empleado->cod_trabajador }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Sucursal --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $m->sucursal->nombre ?? 'GPS / Remoto' }}
                                        </span>
                                    </td>

                                    {{-- Entrada --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="text-sm font-bold text-gray-800">{{ $m->created_at->format('H:i') }}</div>
                                        <div class="text-[10px] text-gray-400 uppercase">{{ $m->created_at->format('d M') }}</div>
                                        
                                        @if($m->fuera_horario)
                                            <span class="text-[9px] text-orange-600 font-bold block">TARDE</span>
                                        @endif
                                    </td>

                                    {{-- Salida --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($m->salida)
                                            <div class="text-sm font-bold text-gray-800">{{ $m->salida->created_at->format('H:i') }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase">{{ $m->salida->created_at->format('d M') }}</div>
                                        @else
                                            <span class="text-gray-300 text-sm">--:--</span>
                                        @endif
                                    </td>

                                    {{-- Estado (Badges del Historial original) --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($m->salida)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Completo
                                            </span>
                                        @else
                                            @if(!$m->created_at->isToday())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Sin Cierre
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                                                    En Curso
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Botón Modal --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        {{-- Lógica para preparar textos del modal --}}
                                        @php
                                            $estadoTexto = '';
                                            $estadoClase = '';
                                            if ($m->salida) {
                                                if ($m->salida->es_olvido || $m->salida->fuera_horario) {
                                                    $estadoTexto = 'Jornada Finalizada (Con Observaciones)';
                                                    $estadoClase = 'bg-orange-100 text-orange-800 border-orange-200';
                                                } else {
                                                    $estadoTexto = 'Jornada Completada Exitosamente';
                                                    $estadoClase = 'bg-green-100 text-green-800 border-green-200';
                                                }
                                            } else {
                                                if (!$m->created_at->isToday()) {
                                                    $estadoTexto = 'Cierre Pendiente (Olvido de Salida)';
                                                    $estadoClase = 'bg-red-100 text-red-800 border-red-200';
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
                                            fotoSalidaFull: '{{ ($m->salida && $m->salida->ubi_foto_full)? Storage::url($m->salida->ubi_foto_full): null }}'
                                        })" 
                                        class="text-gray-400 hover:text-blue-600 transition-colors p-2 rounded-full hover:bg-blue-50">
                                            <i class="fa-solid fa-eye"></i>
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

    {{-- MODAL EXPEDIENTE (Conservado Intacto) --}}
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
            // DataTables con estilo minimalista
            let table = new DataTable('#tablaMarcaciones', {
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json',
                    search: "Buscar:",
                },
                columnDefs: [{ orderable: false, targets: [5] }] // Desactivar orden en columna Acción
            });

            // Lógica del Modal (Copiada del original)
            let map, marker, geocoder;
            let currentData = {};

            function initMap() {
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
            /* Limpieza visual de DataTables */
            .dataTables_wrapper .dataTables_filter input {
                border-radius: 0.375rem;
                border: 1px solid #d1d5db;
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .dataTables_wrapper .dataTables_length select {
                border-radius: 0.375rem;
                border: 1px solid #d1d5db;
                padding: 0.25rem 2rem 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            table.dataTable.no-footer { border-bottom: 0; }
        </style>
    @endpush
</x-app-layout>