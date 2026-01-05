<x-app-layout title="Marcación de Asistencia">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight text-center">
            {{ __('Registro de Asistencia') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4"> {{-- Padding lateral para evitar bordes pegados en móviles --}}
        
        {{-- Contenedor centrado y con ancho máximo de celular --}}
        <div class="max-w-md mx-auto space-y-6"> 

            {{-- 1. Tarjeta de Reloj en Tiempo Real --}}
            <div class="bg-white shadow-lg rounded-2xl p-6 text-center border-t-4 border-blue-600 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-2 opacity-10">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                </div>
                <p class="text-gray-500 text-xs uppercase tracking-widest font-bold mb-1">Hora Actual</p>
                <div id="reloj-tiempo-real" class="text-5xl font-black text-gray-800 tracking-tight">
                    --:--:--
                </div>
                <p class="text-blue-600 font-medium text-sm mt-2 uppercase" id="fecha-actual">
                    {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                </p>
            </div>

            {{-- Mensajes de Éxito/Error --}}
            @if (session('success'))
                <div class="p-4 rounded-xl bg-green-50 border-l-4 border-green-500 text-green-700 shadow-sm animate-pulse">
                    <p class="font-bold">¡Excelente!</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            @elseif (session('error'))
                <div class="p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 shadow-sm">
                    <p class="font-bold">Error</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            @endif

            {{-- 2. Formulario Principal --}}
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6">
                    <form action="{{ route('horarios.store') }}" method="POST" enctype="multipart/form-data" id="form-marcacion">
                        @csrf
                        
                        {{-- Inputs Ocultos para Coordenadas --}}
                        <input type="hidden" name="latitud" id="latitud">
                        <input type="hidden" name="longitud" id="longitud">
                        <input type="hidden" name="ubicacion" id="ubicacion_texto" value="">

                        <div class="space-y-6">

                            {{-- Estado del GPS --}}
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <div id="gps-icon" class="animate-bounce mr-2 text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </div>
                                    <span id="gps-status" class="text-sm text-gray-500 font-medium">Buscando ubicación...</span>
                                </div>
                                <div id="gps-accuracy" class="text-xs text-gray-400"></div>
                            </div>

                            {{-- Selector de Tipo (Botones Grandes) --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">Tipo de Registro</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="tipo_marcacion" value="1" class="peer sr-only" checked>
                                        <div class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-xl transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 group-hover:bg-gray-50">
                                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2 peer-checked:bg-blue-500 peer-checked:text-white transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                            </div>
                                            <span class="font-bold text-gray-600 peer-checked:text-blue-700">Entrada</span>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer group">
                                        <input type="radio" name="tipo_marcacion" value="2" class="peer sr-only">
                                        <div class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-xl transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-50 group-hover:bg-gray-50">
                                            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-2 peer-checked:bg-red-500 peer-checked:text-white transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                            </div>
                                            <span class="font-bold text-gray-600 peer-checked:text-red-700">Salida</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Input de Cámara (Estilo App) --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">Evidencia (Selfie)</label>
                                <div class="relative w-full h-48 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center overflow-hidden group hover:border-blue-400 transition-colors">
                                    {{-- Preview de la imagen --}}
                                    <img id="preview-foto" class="absolute inset-0 w-full h-full object-cover hidden" />
                                    
                                    {{-- Placeholder --}}
                                    <div id="placeholder-foto" class="text-center p-4">
                                        <div class="w-12 h-12 mx-auto bg-gray-200 rounded-full flex items-center justify-center text-gray-500 mb-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        </div>
                                        <p class="text-sm text-gray-500 font-medium">Tocar para tomar foto</p>
                                    </div>

                                    {{-- Input real transparente --}}
                                    <input type="file" name="ubi_foto" id="input-foto" accept="image/*" capture="user" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="previewImage(event)">
                                </div>
                            </div>

                            {{-- Botón de Guardar (Estilo solicitado) --}}
                            <div class="pt-2">
                                <button type="submit" id="btn-marcar" disabled class="w-full text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-lg px-5 py-4 text-center shadow-lg transform transition-transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Registrar Marcación
                                </button>
                                <p class="text-center text-xs text-gray-400 mt-2">Se registrará tu ubicación actual.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- SCRIPTS NECESARIOS --}}
    @push('scripts')
    <script>
        // 1. RELOJ EN TIEMPO REAL
        function actualizarReloj() {
            const ahora = new Date();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            const segundos = String(ahora.getSeconds()).padStart(2, '0');
            document.getElementById('reloj-tiempo-real').textContent = `${horas}:${minutos}:${segundos}`;
        }
        setInterval(actualizarReloj, 1000);
        actualizarReloj(); // Ejecutar inmediatamente

        const btnMarcar = document.getElementById('btn-marcar');
    const statusGps = document.getElementById('gps-status');
    const inputLat = document.getElementById('latitud');
    const inputLng = document.getElementById('longitud');
    const gpsAccuracyText = document.getElementById('gps-accuracy');
    
    // Configuración agresiva para forzar GPS
    const options = {
        enableHighAccuracy: true, // Pide encender el GPS real
        timeout: 20000,           // Esperar hasta 20 segundos por una buena señal
        maximumAge: 0             // No usar caché, buscar señal fresca
    };

    let watchId = null;

    if ("geolocation" in navigator) {
        // Usamos watchPosition en lugar de getCurrentPosition
        // watchPosition se queda escuchando y mejora la precisión con los segundos
        watchId = navigator.geolocation.watchPosition(success, error, options);
    } else {
        statusGps.textContent = "Navegador incompatible";
    }

    function success(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const acc = Math.round(position.coords.accuracy); // Precisión en metros

        // Actualizamos visualmente siempre para que el usuario vea que algo pasa
        gpsAccuracyText.textContent = `Precisión actual: +/- ${acc}m`;

        // LÓGICA DE FILTRADO
        if (acc <= 100) { 
            // Si la precisión es buena (menos de 100m)
            inputLat.value = lat;
            inputLng.value = lng;
            
            statusGps.textContent = "Ubicación Precisa";
            statusGps.classList.remove('text-gray-500', 'text-red-500', 'text-orange-500');
            statusGps.classList.add('text-green-600', 'font-bold');
            
            // Habilitamos botón (validamos también foto si quieres)
            btnMarcar.disabled = false;
            btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed');

            // Opcional: Detener la búsqueda si ya logramos algo muy bueno (ej: < 20m) para ahorrar batería
            if (acc < 20) {
                // navigator.geolocation.clearWatch(watchId);
            }

        } else {
            // Si la precisión es mala (> 100m)
            statusGps.textContent = "Calibrando GPS... (Mala señal)";
            statusGps.classList.add('text-orange-500');
            
            // Deshabilitamos botón para evitar fraudes por mala ubicación
            btnMarcar.disabled = true;
            btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    function error(err) {
        console.warn('ERROR(' + err.code + '): ' + err.message);
        statusGps.textContent = "Error: Enciende el GPS";
        statusGps.classList.add('text-red-500');
    }

        // 3. PREVIEW DE FOTO
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
                    validarFormulario();
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        // 4. VALIDACIÓN SIMPLE
        function validarFormulario() {
            const lat = document.getElementById('latitud').value;
            const foto = document.getElementById('input-foto').files.length;
            
            // Solo habilitar si hay coordenadas y (opcionalmente) si hay foto
            if (lat && lat.length > 0) {
                btnMarcar.disabled = false;
                btnMarcar.classList.remove('opacity-50');
            }
        }
    </script>
    @endpush
</x-app-layout>