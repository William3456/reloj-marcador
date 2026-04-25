@php
    $esEditar = isset($permiso);

    // LÓGICA DE UBICACIÓN LIBRE
    // 1. Valor por defecto: 0 (No)
    $valorUbicacionLibre = 0;

    if (old('ubicacion_libre') !== null) {
        // 2. Si hay un old (recarga por validación), usamos ese valor
        $valorUbicacionLibre = old('ubicacion_libre');
    } elseif ($esEditar) {
        // 3. En EDITAR: Si cantidad_mts es NULL, Ubicación Libre es "Sí" (1), sino "No" (0)
        $valorUbicacionLibre = is_null($permiso->cantidad_mts) ? 1 : 0;
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- ===================== INFORMACIÓN DE ORIGEN (Solo en Edit) ===================== --}}
    @if ($esEditar)
        <div class="col-span-1 md:col-span-2 mb-2 p-4 bg-gray-50 border border-gray-200 rounded-xl flex flex-wrap gap-6 items-center">
            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Origen del Permiso</span>
                @if($permiso->app_creacion == 2)
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-lg border border-blue-200">
                        <i class="fa-solid fa-mobile-screen mr-1.5"></i> App Móvil
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">
                        <i class="fa-solid fa-user-tie mr-1.5"></i> Panel de Control
                    </span>
                @endif
            </div>

            <div class="w-px h-8 bg-gray-200 hidden md:block"></div>

            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estado de Solicitud</span>
                @if($permiso->estado_solicitud == 1)
                    <span class="inline-flex items-center px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-lg border border-yellow-200">En Revisión</span>
                @elseif($permiso->estado_solicitud == 2)
                    <span class="inline-flex items-center px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">Aprobado</span>
                @elseif($permiso->estado_solicitud == 3)
                    <span class="inline-flex items-center px-2.5 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200">Rechazado</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 bg-gray-200 text-gray-600 text-xs font-bold rounded-lg border border-gray-300">Asignación Directa</span>
                @endif
            </div>
        </div>
    @endif

    {{-- ===================== Sucursal ===================== --}}
    <div>
        <x-input-label for="id_sucursal" value="Sucursal" />
        <select id="id_sucursal" {{ $esEditar ? 'disabled' : '' }} class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500
                    {{ $esEditar ? 'bg-gray-100 cursor-not-allowed opacity-75' : '' }}">
            <option value="">Seleccione una sucursal</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}" @selected(old('id_sucursal', $permiso->empleado->id_sucursal ?? null) == $sucursal->id)>
                    {{ $sucursal->nombre }}
                </option>
            @endforeach
        </select>
        @if ($esEditar)
            <input type="hidden" name="id_sucursal" value="{{ $permiso->empleado->id_sucursal }}">
        @endif
        @error('id_sucursal')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ===================== Empleado ===================== --}}
    <div>
        <x-input-label for="id_empleado" value="Empleado" />
        <select id="id_empleado" {{ $esEditar ? 'disabled' : '' }} class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500
                    {{ $esEditar ? 'bg-gray-100 cursor-not-allowed opacity-75' : '' }}" name="id_empleado">
            @if ($esEditar)
                <option value="{{ $permiso->id_empleado }}" selected>
                    {{ $permiso->empleado->cod_trabajador }} - {{ $permiso->empleado->nombres }}
                </option>
            @else
                <option value="">Seleccione un empleado</option>
            @endif
        </select>
        @if ($esEditar)
            <input type="hidden" name="id_empleado" value="{{ $permiso->id_empleado }}">
        @endif
        @error('id_empleado')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ===================== Tipo permiso ===================== --}}
    <div class="md:col-span-2">
        <x-input-label for="id_tipo_permiso" value="Tipo de permiso" />
        <select id="tipo_permiso" name="id_tipo_permiso" required class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione un tipo</option>
            @foreach ($tiposPermiso as $tipo)
                <option value="{{ $tipo->id }}" 
                    data-codigo="{{ $tipo->codigo }}"
                    data-distancia="{{ $tipo->requiere_distancia }}"
                    {{-- LÓGICA DE DATAS DINÁMICOS --}}
                    data-fechas="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 0 : $tipo->requiere_fechas }}" 
                    data-fechaunica="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 1 : 0 }}"
                    data-horas="{{ $tipo->codigo === 'PERMISO_POR_HORAS' ? 1 : 0 }}"
                    data-dias="{{ $tipo->requiere_dias }}"
                    data-valor="{{ in_array($tipo->codigo, ['LLEGADA_TARDE', 'SALIDA_TEMPRANA']) ? 1 : 0 }}"
                    @selected(old('id_tipo_permiso', $permiso?->id_tipo_permiso) == $tipo->id)>
                    {{ $tipo->nombre }}
                </option>
            @endforeach
        </select>
        @error('id_tipo_permiso')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ===================== Motivo ===================== --}}
    <div class="md:col-span-2">
        <x-input-label for="motivo" value="Motivo" />
        <textarea id="motivo" name="motivo" required rows="2" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: Permiso autorizado por jefatura">{{ old('motivo', $permiso?->motivo) }}</textarea>
        @error('motivo')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="1" {{ old('estado', $permiso->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $permiso->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>

    {{-- ===================== NUEVO CAMPO: Ubicación Libre ===================== --}}
    <div id="campo_ubicacion_libre" class="hidden">
        <x-input-label for="ubicacion_libre" value="¿Ubicación libre?" />
        <select id="ubicacion_libre" name="ubicacion_libre" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="0" @selected($valorUbicacionLibre == 0)>No</option>
            <option value="1" @selected($valorUbicacionLibre == 1)>Sí</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Si selecciona "Sí", no se validará el rango de metros.</p>
    </div>

    {{-- ===================== Campos dinámicos ===================== --}}
    
    {{-- Distancia --}}
    <div id="campo_distancia" class="hidden">
        <x-input-label for="cantidad_mts" value="Distancia permitida (mts)" />
        <x-text-input id="cantidad_mts" name="cantidad_mts" type="number" min="1" value="{{ old('cantidad_mts', $permiso?->cantidad_mts) }}" class="mt-1 block w-full" />
    </div>

    {{-- Valor Minutos --}}
    <div id="campo_valor" class="hidden">
        <x-input-label for="valor" value="Valor del permiso (minutos)" />
        <x-text-input id="valor" name="valor" type="number" min="1" value="{{ old('valor', $permiso?->valor) }}" class="mt-1 block w-full" />
    </div>

    {{-- 🌟 NUEVO: FECHA ÚNICA Y RANGO DE HORAS --}}
    <div id="campo_fecha_unica" class="hidden">
        <x-input-label for="fecha_unica" value="Fecha del permiso" />
        <x-text-input id="fecha_unica" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $permiso?->fecha_inicio) }}" class="mt-1 block w-full" min="{{ !$esEditar ? now()->toDateString() : '' }}" />
    </div>

    <div id="campo_horas" class="hidden md:col-span-2">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="hora_ini" value="Hora de Salida" />
                <x-text-input id="hora_ini" name="hora_ini" type="time" value="{{ old('hora_ini', $permiso?->hora_ini ? \Carbon\Carbon::parse($permiso->hora_ini)->format('H:i') : '') }}" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="hora_fin_permiso" value="Hora de Regreso" />
                <x-text-input id="hora_fin_permiso" name="hora_fin" type="time" value="{{ old('hora_fin', $permiso?->hora_fin ? \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') : '') }}" class="mt-1 block w-full" />
            </div>
        </div>
    </div>

    {{-- Rango de Fechas Normal --}}
    <div id="campo_fecha_inicio_rango" class="hidden">
        <x-input-label for="fecha_inicio_rango" value="Fecha inicio" />
        <x-text-input id="fecha_inicio_rango" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $permiso?->fecha_inicio) }}" min="{{ !$esEditar ? now()->toDateString() : '' }}" class="mt-1 block w-full" />
    </div>
    
    <div id="campo_fecha_fin_rango" class="hidden">
        <x-input-label for="fecha_fin" value="Fecha fin" />
        <x-text-input id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $permiso?->fecha_fin) }}" class="mt-1 block w-full" />
    </div>

    {{-- Días --}}
    <div id="campo_dias" class="hidden">
        <x-input-label for="dias_activa" value="Días hábiles activos" />
        <x-text-input id="dias_activa" name="dias_activa" type="number" min="1" value="{{ old('dias_activa', $permiso?->dias_activa) }}" class="mt-1 block w-full" />
    </div>
</div>

<script>
    const ES_EDITAR = @json($esEditar);

    function toggle(id, show) {
        const el = document.getElementById(id);
        if (!el) return;
        
        if (show) {
            el.classList.remove('hidden');
            el.querySelectorAll('input, select').forEach(i => i.removeAttribute('disabled'));
        } else {
            el.classList.add('hidden');
            el.querySelectorAll('input, select').forEach(i => i.setAttribute('disabled', 'true'));
        }
    }

    function actualizarCampos() {
        const tipo = document.getElementById('tipo_permiso');
        if (!tipo || tipo.selectedIndex <= 0) return;

        const opt = tipo.options[tipo.selectedIndex];
        
        // 1. Ubicación Libre (Por el código en vez de value 1)
        const esFueraRango = (opt.dataset.codigo === 'FUERA_RANGO');
        toggle('campo_ubicacion_libre', esFueraRango);

        let mostrarDistancia = (opt.dataset.distancia === '1');
        if (esFueraRango) {
            const ubicacionLibre = document.getElementById('ubicacion_libre').value;
            if (ubicacionLibre == '1') mostrarDistancia = false;
        }
        
        toggle('campo_distancia', mostrarDistancia);
        const inputDistancia = document.getElementById('cantidad_mts');
        if (inputDistancia) {
            if (mostrarDistancia) inputDistancia.setAttribute('required', 'true');
            else inputDistancia.removeAttribute('required');
        }

        // 2. Fechas Rango Normal
        const reqFechas = (opt.dataset.fechas === '1');
        toggle('campo_fecha_inicio_rango', reqFechas);
        toggle('campo_fecha_fin_rango', reqFechas);
        
        if(reqFechas){
            document.getElementById('fecha_inicio_rango').setAttribute('required', 'true');
            document.getElementById('fecha_fin').setAttribute('required', 'true');
        } else {
            document.getElementById('fecha_inicio_rango').removeAttribute('required');
            document.getElementById('fecha_fin').removeAttribute('required');
        }

        // 3. Fecha Única
        const reqFechaUnica = (opt.dataset.fechaunica === '1');
        toggle('campo_fecha_unica', reqFechaUnica);
        if(reqFechaUnica) document.getElementById('fecha_unica').setAttribute('required', 'true');
        else document.getElementById('fecha_unica').removeAttribute('required');

        // 4. Horas Rango
        const reqHoras = (opt.dataset.horas === '1');
        toggle('campo_horas', reqHoras);
        if(reqHoras){
            document.getElementById('hora_ini').setAttribute('required', 'true');
            document.getElementById('hora_fin_permiso').setAttribute('required', 'true');
        } else {
            document.getElementById('hora_ini').removeAttribute('required');
            document.getElementById('hora_fin_permiso').removeAttribute('required');
        }

        // Resto de campos
        toggle('campo_dias', opt.dataset.dias === '1');
        toggle('campo_valor', opt.dataset.valor === '1');
    }

    function actualizarMinFechaFin() {
        const fechaInicio = $('#fecha_inicio_rango').val();
        if (fechaInicio) {
            $('#fecha_fin').attr('min', fechaInicio);
            if ($('#fecha_fin').val() && $('#fecha_fin').val() < fechaInicio) {
                $('#fecha_fin').val('');
            }
        } else {
            $('#fecha_fin').removeAttr('min');
        }
    }

    $('#fecha_inicio_rango').on('change', actualizarMinFechaFin);

    $(document).ready(function () {
        if (!ES_EDITAR) {
            $('#id_sucursal').on('change', function () {
                let sucursalID = $(this).val();
                if (!sucursalID) return;

                $.getJSON('/api/empleados/sucursal/' + sucursalID, function (data) {
                    let sel = $('#id_empleado');
                    sel.empty().append('<option value="">Seleccione un empleado</option>');
                    data.forEach(e => {
                        sel.append(`<option value="${e.id}">${e.cod_trabajador} - ${e.nombres}</option>`);
                    });
                });
            });
        }

        $('#tipo_permiso').on('change', actualizarCampos);
        $('#ubicacion_libre').on('change', actualizarCampos);

        actualizarCampos();
        actualizarMinFechaFin();
    });
</script>