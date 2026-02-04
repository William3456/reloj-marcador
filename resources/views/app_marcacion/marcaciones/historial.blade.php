<x-app-layout title="Mi Historial">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial</h2>
        </div>
    </x-slot>

    <div class="py-6 px-4 max-w-md mx-auto">
        {{-- Filtros --}}
        <form action="{{ route('marcacion.historial') }}" method="GET"
            class="bg-white p-4 rounded-2xl shadow-sm mb-6 border border-gray-100">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Desde</label>
                    <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"
                        class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"
                        class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 shadow-sm">
                </div>
            </div>

            <div class="flex gap-3 mt-3">
                <a href="{{ route('marcacion.historial') }}"
                    class="flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-500 rounded-xl hover:bg-gray-200 active:scale-95 transition-all"
                    title="Volver al mes actual">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </a>
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl text-sm font-bold shadow-md active:scale-95 transition-transform flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                    Filtrar Historial
                </button>
            </div>
        </form>

        {{-- Listado Estilo Timeline --}}
        <div class="space-y-6">
            @forelse($historial as $dia)
                <div class="relative">

                    {{-- 1. CABECERA DE FECHA (Estilo Original mejorado con contador) --}}
                    <div class="sticky top-0 z-10 bg-gray-50 py-2 mb-2 flex items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-black text-blue-800 bg-blue-100 px-3 py-1 rounded-full uppercase shadow-sm">
                                {{ $dia['fecha']->locale('es')->isoFormat('dddd D') }}
                            </span>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase">
                                {{ $dia['fecha']->locale('es')->isoFormat('MMMM') }}
                            </span>
                        </div>

                        {{-- Badge de Conteo (Turnos: 3 | OK: 2) --}}
                        <div
                            class="px-2 py-0.5 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-500 shadow-sm">
                            {{ $dia['completados'] }}/{{ $dia['total_turnos'] }} Turnos
                        </div>
                    </div>

                    {{-- 2. TARJETA CONTENEDORA (Tu estilo original) --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        @if($dia['total_turnos'] == 0)
                            <div class="p-8 text-center">
                                <div
                                    class="w-12 h-12 bg-blue-50 text-blue-400 rounded-full flex items-center justify-center mx-auto mb-3">
                                    {{-- Icono de "Chill" o Calendario --}}
                                    <i class="fa-regular fa-calendar-check text-xl"></i>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800">No hay turnos asignados</h3>
                                <p class="text-xs text-gray-500 mt-1">Este día no tienes horarios laborales programados.</p>

                                {{-- Opcional: Si por error marcó en su día libre, podrías listar las marcaciones aquí --}}
                            </div>

                        @else
                            @foreach($dia['detalles'] as $index => $turno)

                                {{-- SEPARADOR DE TURNO (Para saber que es Turno 1, Turno 2...) --}}
                                <div
                                    class="bg-gray-50/50 px-4 py-1.5 border-b border-gray-100 border-t {{ $index == 0 ? 'border-t-0' : 'border-t-gray-100' }} flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                        Turno {{ $index + 1 }} • {{ substr($turno['horario_info']->hora_ini, 0, 5) }} -
                                        {{ substr($turno['horario_info']->hora_fin, 0, 5) }}
                                    </span>
                                    @if($turno['estado'] == 'perdido')
                                        <span class="text-[9px] font-bold text-red-500 bg-red-50 px-2 rounded-full">AUSENTE</span>
                                    @endif
                                </div>

                                {{-- CASO A: TURNO COMPLETADO (Muestro Entrada y Salida con tu diseño) --}}
                                @if($turno['estado'] == 'completado')

                                    {{-- ENTRADA --}}
                                    <div onclick="abrirDetalle(this)"
                                        class="flex items-center p-4 cursor-pointer hover:bg-gray-50 active:bg-blue-50 transition-colors border-b border-gray-50"
                                        data-tipo="Entrada" data-hora="{{ $turno['entrada']->created_at->format('h:i A') }}"
                                        data-fecha="{{ $turno['entrada']->created_at->locale('es')->isoFormat('dddd, D [de] MMMM') }}"
                                        data-sucursal="{{ $turno['entrada']->sucursal->nombre ?? 'Ubicación GPS' }}"
                                        data-foto="{{ Storage::url($turno['entrada']->ubi_foto) }}"
                                        data-lat="{{ $turno['entrada']->latitud }}" data-lng="{{ $turno['entrada']->longitud }}"
                                        data-etiqueta="{{ $turno['entrada']->fuera_horario ? 'Tarde' : '' }}"
                                        data-clase-color="bg-orange-100 text-orange-700">

                                        {{-- Icono Entrada --}}
                                        <div
                                            class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-green-100 text-green-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                                                </path>
                                            </svg>
                                        </div>

                                        <div class="ml-4 flex-grow">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-bold text-gray-800">Entrada</p>
                                                @if($turno['entrada']->fuera_horario)
                                                    <span
                                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded border bg-orange-100 text-orange-700 border-orange-200">Tarde</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                {{ $turno['entrada']->created_at->format('h:i A') }} •
                                                {{ $turno['entrada']->sucursal->nombre ?? 'GPS' }}
                                            </p>
                                        </div>
                                        <div class="text-gray-300">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </div>
                                    </div>

                                    {{-- SALIDA --}}
                                    @if($turno['salida'])
                                        <div onclick="abrirDetalle(this)"
                                            class="flex items-center p-4 cursor-pointer hover:bg-gray-50 active:bg-blue-50 transition-colors border-b border-gray-50"
                                            data-tipo="Salida" data-hora="{{ $turno['salida']->created_at->format('h:i A') }}"
                                            data-fecha="{{ $turno['salida']->created_at->locale('es')->isoFormat('dddd, D [de] MMMM') }}"
                                            data-sucursal="{{ $turno['salida']->sucursal->nombre ?? 'Ubicación GPS' }}"
                                            data-foto="{{ Storage::url($turno['salida']->ubi_foto) }}"
                                            data-lat="{{ $turno['salida']->latitud }}" data-lng="{{ $turno['salida']->longitud }}">

                                            {{-- Icono Salida --}}
                                            <div
                                                class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-red-100 text-red-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                                    </path>
                                                </svg>
                                            </div>

                                            <div class="ml-4 flex-grow">
                                                <p class="text-sm font-bold text-gray-800">Salida</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $turno['salida']->created_at->format('h:i A') }} •
                                                    {{ $turno['salida']->sucursal->nombre ?? 'GPS' }}
                                                </p>
                                            </div>
                                            <div class="text-gray-300">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </div>
                                        </div>
                                    @else
                                        {{-- SALIDA PENDIENTE/OLVIDADA (Visual gris dentro del mismo turno) --}}
                                        <div class="flex items-center p-4 bg-gray-50/50">
                                            <div
                                                class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-gray-200 text-gray-400 border border-gray-300 border-dashed">
                                                <i class="fa-regular fa-clock"></i>
                                            </div>
                                            <div class="ml-4 flex-grow">
                                                <p class="text-sm font-bold text-gray-400">Salida pendiente</p>
                                                <p class="text-[10px] text-gray-400 italic">No se registró salida</p>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- CASO B: TURNO PERDIDO (Visual Rojo) --}}
                                @elseif($turno['estado'] == 'perdido')
                                    <div class="flex items-center p-4 bg-red-50 border-b border-red-100">
                                        <div
                                            class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-white text-red-400 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4 flex-grow">
                                            <p class="text-sm font-bold text-red-700">Turno Perdido</p>
                                            <p class="text-[10px] text-red-500">
                                                No asististe al turno de {{ substr($turno['horario_info']->hora_ini, 0, 5) }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- CASO C: FUTURO --}}
                                @elseif($turno['estado'] == 'pendiente')
                                    <div class="flex items-center p-4 opacity-50">
                                        <div
                                            class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-gray-100 text-gray-400">
                                            <i class="fa-regular fa-calendar"></i>
                                        </div>
                                        <div class="ml-4 flex-grow">
                                            <p class="text-sm font-bold text-gray-500">Programado</p>
                                            <p class="text-xs text-gray-400">Turno futuro</p>
                                        </div>
                                    </div>
                                @endif

                            @endforeach
                        @endif
                    </div>
                </div>
            @empty
                {{-- Empty State (Igual al original) --}}
                <div class="text-center py-20">
                    <div
                        class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">No hay registros en este rango.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL DETALLE --}}
    <div id="modal-detalle" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"
            onclick="cerrarDetalle()"></div>

        <div
            class="fixed inset-x-0 bottom-0 bg-white rounded-t-[30px] shadow-2xl transform transition-transform duration-300 overflow-hidden max-w-md mx-auto">
            <div class="flex justify-center pt-3" onclick="cerrarDetalle()">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
            </div>

            <div class="p-6 pb-10">
                {{-- Encabezado --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 id="modal-titulo" class="text-2xl font-black text-gray-800 uppercase tracking-tight">---
                            </h3>
                            <span id="modal-etiqueta"
                                class="hidden text-[10px] font-bold px-2 py-0.5 rounded border"></span>
                        </div>
                        <p id="modal-fecha" class="text-blue-600 font-medium text-sm"></p>
                    </div>
                    <button onclick="cerrarDetalle()"
                        class="p-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Foto con Fallback (Mejora visual) --}}
                <div
                    class="mb-6 bg-gray-100 rounded-2xl overflow-hidden border border-gray-100 shadow-inner min-h-[14rem]">
                    <img id="modal-img" src="" class="w-full h-56 object-cover" alt="Evidencia" {{-- Si la imagen falla,
                        muestra un placeholder gris con texto --}}
                        onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=Sin+Evidencia';">
                </div>

                {{-- Tarjeta de Ubicación --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">

                    {{-- SUCURSAL --}}
                    <div class="flex items-center mb-4 pb-4 border-b border-gray-100">
                        <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sucursal Asignada
                            </p>
                            <p id="modal-sucursal-texto" class="text-sm font-bold text-gray-900">---</p>
                        </div>
                    </div>

                    {{-- DIRECCIÓN GPS --}}
                    <div class="flex items-center mb-3">
                        <div class="bg-blue-100 p-2 rounded-lg text-blue-600 mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dirección GPS
                                Detectada</p>
                            <p id="modal-direccion-gps" class="text-sm font-bold text-gray-900 leading-tight">Obteniendo
                                dirección...</p>
                        </div>
                    </div>

                    {{-- Mapa --}}
                    <div id="mapa-modal" class="w-full h-40 rounded-xl overflow-hidden bg-gray-100 relative z-0"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mapModal;
        let markerModal;
        let geocoderModal;

        function abrirDetalle(elemento) {
            const tipo = elemento.getAttribute('data-tipo');
            const hora = elemento.getAttribute('data-hora');
            const fecha = elemento.getAttribute('data-fecha');
            const sucursal = elemento.getAttribute('data-sucursal');
            const fotoUrl = elemento.getAttribute('data-foto');
            const etiqueta = elemento.getAttribute('data-etiqueta');
            const claseColor = elemento.getAttribute('data-clase-color');
            const lat = parseFloat(elemento.getAttribute('data-lat'));
            const lng = parseFloat(elemento.getAttribute('data-lng'));

            // UI Básica
            document.getElementById('modal-titulo').innerText = tipo;
            document.getElementById('modal-fecha').innerText = fecha + ' • ' + hora;
            document.getElementById('modal-img').src = fotoUrl; // El onerror del HTML maneja si falla
            document.getElementById('modal-sucursal-texto').innerText = sucursal;

            // Resetear dirección GPS
            document.getElementById('modal-direccion-gps').innerText = "Cargando dirección exacta...";
            document.getElementById('modal-direccion-gps').classList.add('text-gray-400');

            // Badge
            const badgeModal = document.getElementById('modal-etiqueta');
            if (etiqueta) {
                badgeModal.innerText = etiqueta;
                badgeModal.className = 'text-[10px] font-bold px-2 py-0.5 rounded border ' + claseColor;
                badgeModal.classList.remove('hidden');
            } else {
                badgeModal.classList.add('hidden');
            }

            const modal = document.getElementById('modal-detalle');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            initOrUpdateMap(lat, lng);
            obtenerDireccionGoogle(lat, lng);
        }

        function initOrUpdateMap(lat, lng) {
            const position = { lat: lat, lng: lng };
            if (!mapModal) {
                setTimeout(() => {
                    mapModal = new google.maps.Map(document.getElementById("mapa-modal"), {
                        center: position,
                        zoom: 16,
                        disableDefaultUI: true,
                        zoomControl: true,
                        gestureHandling: 'cooperative'
                    });
                    markerModal = new google.maps.Marker({
                        position: position,
                        map: mapModal,
                        animation: google.maps.Animation.DROP
                    });
                }, 100);
            } else {
                mapModal.setCenter(position);
                markerModal.setPosition(position);
                setTimeout(() => {
                    google.maps.event.trigger(mapModal, 'resize');
                    mapModal.setCenter(position);
                }, 100);
            }
        }

        function obtenerDireccionGoogle(lat, lng) {
            if (!geocoderModal) {
                geocoderModal = new google.maps.Geocoder();
            }
            const latlng = { lat: lat, lng: lng };

            geocoderModal.geocode({ location: latlng }, (results, status) => {
                const elDireccion = document.getElementById('modal-direccion-gps');

                if (status === "OK") {
                    if (results[0]) {
                        elDireccion.innerText = results[0].formatted_address;
                        elDireccion.classList.remove('text-gray-400');
                    } else {
                        elDireccion.innerText = "Ubicación (" + lat + ", " + lng + ")";
                    }
                } else {
                    console.error("Fallo Geocoder: " + status);
                    elDireccion.innerText = "No se pudo obtener la dirección.";
                }
            });
        }

        function cerrarDetalle() {
            document.getElementById('modal-detalle').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</x-app-layout>