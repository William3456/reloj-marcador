<x-app-layout title="Historial de Asistencia">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Historial de Marcaciones
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">

                {{-- SECCIÓN 1: FILTROS (Se mantiene igual, oculto por brevedad) --}}
                <form action="{{ route('marcaciones.index') }}" method="GET" class="mb-6 border-b border-gray-100 pb-6">
                    {{-- ... (Tu código de filtros original va aquí) ... --}}
                    {{-- Solo asegurate de incluir el botón y inputs tal cual los tenías --}}
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Desde</label>
                            <input type="date" name="desde" value="{{ request('desde') ?? date('Y-m-d') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasta</label>
                            <input type="date" name="hasta" value="{{ request('hasta') ?? date('Y-m-d') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal</label>
                            <select name="sucursal" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todas</option>
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empleado</label>
                            <select name="empleado" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todos</option>
                                @foreach($empleadosList as $emp)
                                    <option value="{{ $emp->id }}" {{ request('empleado') == $emp->id ? 'selected' : '' }}>{{ $emp->nombres }} {{ $emp->apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm flex items-center justify-center transition-colors">
                                <i class="fa-solid fa-filter mr-2"></i> Filtrar
                            </button>
                            <a href="{{ route('marcaciones.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded shadow-sm text-sm flex items-center justify-center transition-colors" title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>

                {{-- SECCIÓN 2: TABLA DATA TABLES --}}
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
                                            <span class="text-[9px] text-orange-600 font-bold block mt-0.5">TARDE</span>
                                        @endif

                                        {{-- NUEVO: Permiso Entrada --}}
                                        @if($m->permiso)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-purple-100 text-purple-800 border border-purple-200" title="{{ $m->permiso->motivo }}">
                                                    <i class="fa-solid fa-file-contract mr-1"></i>
                                                    Permiso aplicado
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Salida --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($m->salida)
                                            <div class="text-sm font-bold text-gray-800">{{ $m->salida->created_at->format('H:i') }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase">{{ $m->salida->created_at->format('d M') }}</div>
                                            
                                            {{-- NUEVO: Permiso Salida --}}
                                            @if($m->salida->permiso)
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-purple-100 text-purple-800 border border-purple-200" title="{{ $m->salida->permiso->motivo }}">
                                                        <i class="fa-solid fa-file-contract mr-1"></i>
                                                        Permiso aplicado
                                                    </span>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-300 text-sm">--:--</span>
                                        @endif
                                    </td>

                                    {{-- Estado --}}
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
    @php
        // Lógica de Estado (se mantiene igual)
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

        // Lógica de Permisos (ACTUALIZADA)
        $txtPermisoEntrada = $m->permiso ? $m->permiso->tipoPermiso->nombre : null;
        // Escapamos comillas dobles para no romper el JS
        $motivoEntrada = $m->permiso ? str_replace('"', '&quot;', $m->permiso->motivo) : ''; 

        $txtPermisoSalida  = ($m->salida && $m->salida->permiso) ? $m->salida->permiso->tipoPermiso->nombre : null;
        $motivoSalida = ($m->salida && $m->salida->permiso) ? str_replace('"', '&quot;', $m->salida->permiso->motivo) : '';
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
        fotoSalidaFull: '{{ ($m->salida && $m->salida->ubi_foto_full)? Storage::url($m->salida->ubi_foto_full): null }}',
        
        // DATOS NUEVOS
        permisoEntrada: '{{ $txtPermisoEntrada }}',
        motivoEntrada: '{{ $motivoEntrada }}', 
        permisoSalida: '{{ $txtPermisoSalida }}',
        motivoSalida: '{{ $motivoSalida }}'
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
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                            
                            {{-- Columna 1: Fotos --}}
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1 mb-2">Evidencia Fotográfica</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Foto Entrada --}}
                                    <div class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center relative">
                                        <span class="text-xs font-bold text-green-700 block mb-1">ENTRADA</span>
                                        <input type="hidden" id="fotoEntradaFull">
                                        <input type="hidden" id="fotoSalidaFull">
                                        


                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group">
                                            <img id="imgEntrada" src="" class="w-full h-full object-cover hidden cursor-pointer" onclick="zoomImagen(document.getElementById('fotoEntradaFull').value)">
                                            <span id="noImgEntrada" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <p id="horaEntradaModal" class="text-xs text-gray-600 mt-1 font-mono font-bold">--:--</p>
                                                                                {{-- NUEVO: Badge Flotante para Permiso Entrada --}}
                                        <div id="badgePermisoEntrada" class="hidden absolute top-8 left-1/2 transform -translate-x-1/2 z-10 w-11/12">
                                            
                                        <div id="badgePermisoEntrada" class="hidden mt-2 w-full">
                                            <span id="txtPermisoEntrada" class="block w-full bg-purple-100 text-purple-800 text-[9px] font-bold px-2 py-1.5 rounded-md border border-purple-200 shadow-sm whitespace-normal leading-tight">
                                                ---
                                            </span>
                                        </div>
                                        </div>
                                    </div>

                                    {{-- Foto Salida --}}
                                    <div class="bg-gray-50 rounded-xl p-2 border border-gray-200 text-center relative">
                                        <span class="text-xs font-bold text-red-700 block mb-1">SALIDA</span>
                                       
                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center relative group">
                                            <img id="imgSalida" src="" class="w-full h-full object-cover hidden cursor-pointer" onclick="zoomImagen(document.getElementById('fotoSalidaFull').value)">
                                            <span id="noImgSalida" class="text-gray-400 text-xs">Sin foto</span>
                                        </div>
                                        <p id="horaSalidaModal" class="text-xs text-gray-600 mt-1 font-mono font-bold">--:--</p>
                                         {{-- NUEVO: Badge Flotante para Permiso Salida --}}
                                        <div id="badgePermisoSalida" class="hidden mt-2 w-full">
                                            <span id="txtPermisoSalida" class="block w-full bg-purple-100 text-purple-800 text-[9px] font-bold px-2 py-1.5 rounded-md border border-purple-200 shadow-sm whitespace-normal leading-tight">
                                                ---
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna 2: Mapa (Sin Cambios) --}}
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
            let table = new DataTable('#tablaMarcaciones', {
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json',
                    search: "Buscar:",
                },
                columnDefs: [{ orderable: false, targets: [5] }]
            });

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

    // --- LÓGICA DE PERMISOS CON SALTO DE LÍNEA ---
    
    // 1. Permiso Entrada
    const badgeP_Entrada = document.getElementById('badgePermisoEntrada');
    const txtP_Entrada = document.getElementById('txtPermisoEntrada');
    
    if(data.permisoEntrada) {
        // Usamos innerHTML para meter el <br>
        // Título en negrita (por la clase del padre), Motivo en normal e itálica
        let contenido = `${data.permisoEntrada}`;
        if(data.motivoEntrada) {
            contenido += `<br><span class="font-normal italic opacity-80">${data.motivoEntrada}</span>`;
        }
        txtP_Entrada.innerHTML = contenido;
        badgeP_Entrada.classList.remove('hidden');
    } else {
        badgeP_Entrada.classList.add('hidden');
    }

    // 2. Permiso Salida
    const badgeP_Salida = document.getElementById('badgePermisoSalida');
    const txtP_Salida = document.getElementById('txtPermisoSalida');
    
    if(data.permisoSalida) {
        let contenido = `${data.permisoSalida}`;
        if(data.motivoSalida) {
            contenido += `<br><span class="font-normal italic opacity-80">${data.motivoSalida}</span>`;
        }
        txtP_Salida.innerHTML = contenido;
        badgeP_Salida.classList.remove('hidden');
    } else {
        badgeP_Salida.classList.add('hidden');
    }
    // ------------------------------------------

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