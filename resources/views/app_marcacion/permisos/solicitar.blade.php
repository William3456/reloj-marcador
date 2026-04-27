<x-app-layout title="Solicitar permiso">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight tracking-tight">Solicitar permiso</h2>
        </div>
    </x-slot>

    <div class="py-6 px-4 max-w-md mx-auto mb-20">
        
        {{-- Tarjeta de instrucciones --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-6 flex items-start gap-3 shadow-sm">
            <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-blue-800">Nueva solicitud</h3>
                <p class="text-xs text-blue-600 mt-0.5 leading-snug">
                    Tu solicitud será enviada a jefatura para su aprobación. Recibirás un correo cuando sea procesada.
                </p>
            </div>
        </div>
        {{-- Bloque para mostrar errores ocultos --}}
        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulario principal --}}
        <form action="{{ route('marcacion.permisos.store') }}" method="POST" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 space-y-5">
            @csrf

            {{-- Tipo de permiso --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tipo de permiso</label>
                <select id="tipo_permiso" name="id_tipo_permiso" required onchange="actualizarCampos()"
                    class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-gray-50">
                    <option value="" disabled selected>Selecciona una opción...</option>
                    @foreach ($tiposPermiso as $tipo)
                        <option value="{{ $tipo->id }}" 
                            data-codigo="{{ $tipo->codigo }}"
                            data-distancia="{{ $tipo->requiere_distancia }}"
                            {{-- Si es por horas, apagamos el rango de fechas normal y encendemos la fecha única y horas --}}
                            data-fechas="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 0 : $tipo->requiere_fechas }}" 
                            data-fechaunica="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 1 : 0 }}"
                            data-horas="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 1 : 0 }}"
                            data-dias="{{ $tipo->requiere_dias }}"
                            data-valor="{{ in_array($tipo->codigo, ['LLEGADA_TARDE', 'SALIDA_TEMPRANA']) ? 1 : 0 }}">
                            {{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('id_tipo_permiso') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Motivo --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Motivo / Justificación</label>
                <textarea id="motivo" name="motivo" required rows="3"
                    class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-gray-50"
                    placeholder="Describe brevemente el motivo de tu solicitud..."></textarea>
                @error('motivo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Campos dinámicos --}}
            <div id="contenedor_dinamico" class="space-y-5 border-t border-gray-100 pt-4 hidden">
                
                {{-- Ubicación libre (solo GPS) --}}
                <div id="campo_ubicacion_libre" class="hidden bg-orange-50 p-3 rounded-xl border border-orange-100">
                    <label class="block text-[11px] font-bold text-orange-700 uppercase tracking-wider mb-1.5">¿Ubicación libre?</label>
                    <select id="ubicacion_libre" name="ubicacion_libre" onchange="actualizarCampos()"
                        class="w-full text-sm border-orange-200 rounded-lg focus:ring-orange-500 bg-white shadow-sm">
                        <option value="0">No, especificar rango en metros</option>
                        <option value="1">Sí, permitir marcar en cualquier lugar</option>
                    </select>
                </div>

                {{-- Distancia --}}
                <div id="campo_distancia" class="hidden">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Metros permitidos</label>
                    <div class="relative">
                        <input type="number" id="cantidad_mts" name="cantidad_mts" min="1" placeholder="Ej: 50"
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50 pl-4 pr-12">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs font-bold text-gray-400">
                            mts
                        </div>
                    </div>
                </div>

                {{-- Valor (minutos) --}}
                <div id="campo_valor" class="hidden">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tiempo a solicitar</label>
                    <div class="relative">
                        <input type="number" id="valor" name="valor" min="1" placeholder="Ej: 30"
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50 pl-4 pr-12">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-xs font-bold text-gray-400">
                            min
                        </div>
                    </div>
                </div>

                {{-- Fecha única (para permisos de horas) --}}
                <div id="campo_fecha_unica" class="hidden">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Fecha del permiso</label>
                    <input type="date" id="fecha_unica" name="fecha_inicio" min="{{ now()->toDateString() }}"
                        class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                </div>

                {{-- Horas (rango) --}}
                <div id="campo_horas" class="hidden grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Hora de salida</label>
                        <input type="time" id="hora_ini" name="hora_ini" 
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Hora de regreso</label>
                        <input type="time" id="hora_fin" name="hora_fin" 
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- Fechas normales (grid de 2 columnas) --}}
                <div id="campo_fechas" class="hidden grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Desde</label>
                        {{-- Cambiamos este ID a fecha_inicio_rango --}}
                        <input type="date" id="fecha_inicio_rango" name="fecha_inicio" min="{{ now()->toDateString() }}" onchange="actualizarMinFechaFin()"
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Hasta</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" 
                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                </div>

                {{-- Días activos --}}
                <div id="campo_dias" class="hidden">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Cantidad de días hábiles</label>
                    <input type="number" id="dias_activa" name="dias_activa" min="1" placeholder="Ej: 3"
                        class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                </div>
            </div>

            {{-- Botón submit --}}
            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition-transform active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Enviar solicitud
                </button>
            </div>
        </form>
    </div>

    {{-- Lógica JavaScript --}}
    <script>
        function toggleInput(id, show) {
            const el = document.getElementById(id);
            if (!el) return;
            
            if (show) {
                el.classList.remove('hidden');
                // Habilitar inputs internos para que viajen en el POST
                el.querySelectorAll('input, select').forEach(i => i.removeAttribute('disabled'));
            } else {
                el.classList.add('hidden');
                // Deshabilitar inputs internos para evitar errores de "Required" en campos ocultos
                el.querySelectorAll('input, select').forEach(i => i.setAttribute('disabled', 'true'));
            }
        }

        function actualizarCampos() {
            const tipoSelect = document.getElementById('tipo_permiso');
            const contenedor = document.getElementById('contenedor_dinamico');
            
            if (tipoSelect.selectedIndex <= 0) {
                contenedor.classList.add('hidden');
                return;
            }

            contenedor.classList.remove('hidden');
            const opt = tipoSelect.options[tipoSelect.selectedIndex];
            
            // Evaluamos con el data-codigo en lugar del value=1 para hacerlo a prueba de balas
            const esFueraRango = (opt.getAttribute('data-codigo') === 'FUERA_RANGO');
            
            toggleInput('campo_ubicacion_libre', esFueraRango);

            let mostrarDistancia = (opt.getAttribute('data-distancia') === '1');
            if (esFueraRango) {
                const ubiLibre = document.getElementById('ubicacion_libre').value;
                if (ubiLibre === '1') mostrarDistancia = false;
            }

            toggleInput('campo_distancia', mostrarDistancia);
            if(mostrarDistancia) document.getElementById('cantidad_mts').setAttribute('required', 'true');
            else document.getElementById('cantidad_mts').removeAttribute('required');

            const reqFechas = (opt.getAttribute('data-fechas') === '1');
            toggleInput('campo_fechas', reqFechas);
            if(reqFechas){
                document.getElementById('fecha_inicio_rango').setAttribute('required', 'true');
                document.getElementById('fecha_fin').setAttribute('required', 'true');
            } else {
                document.getElementById('fecha_inicio_rango').removeAttribute('required');
                document.getElementById('fecha_fin').removeAttribute('required');
            }
            
            const reqFechaUnica = (opt.getAttribute('data-fechaunica') === '1');
            toggleInput('campo_fecha_unica', reqFechaUnica);
            if(reqFechaUnica) {
                document.getElementById('fecha_unica').setAttribute('required', 'true');
            } else {
                document.getElementById('fecha_unica').removeAttribute('required');
            }

            const reqHoras = (opt.getAttribute('data-horas') === '1');
            toggleInput('campo_horas', reqHoras);
            if(reqHoras){
                document.getElementById('hora_ini').setAttribute('required', 'true');
                document.getElementById('hora_fin').setAttribute('required', 'true');
            } else {
                document.getElementById('hora_ini').removeAttribute('required');
                document.getElementById('hora_fin').removeAttribute('required');
            }
            toggleInput('campo_dias', opt.getAttribute('data-dias') === '1');
            toggleInput('campo_valor', opt.getAttribute('data-valor') === '1');
        }

        function actualizarMinFechaFin() {
            const fInicio = document.getElementById('fecha_inicio_rango').value;
            const fFin = document.getElementById('fecha_fin');
            if (fInicio) {
                fFin.setAttribute('min', fInicio);
                if (fFin.value && fFin.value < fInicio) fFin.value = '';
            } else {
                fFin.removeAttribute('min');
            }
        }
    </script>
</x-app-layout>