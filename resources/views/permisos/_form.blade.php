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

        {{-- Hidden obligatorio en edit --}}
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

        {{-- Hidden obligatorio en edit --}}
        @if ($esEditar)
            <input type="hidden" name="id_empleado" value="{{ $permiso->id_empleado }}">
        @endif

        @error('id_empleado')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ===================== Tipo permiso ===================== --}}
    <div>
        <x-input-label for="id_tipo_permiso" value="Tipo de permiso" />

        <select id="tipo_permiso" name="id_tipo_permiso" required
            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione un tipo</option>
            @foreach ($tiposPermiso as $tipo)
                <option value="{{ $tipo->id }}" data-distancia="{{ $tipo->requiere_distancia }}"
                    data-fechas="{{ $tipo->requiere_fechas }}" data-dias="{{ $tipo->requiere_dias }}"
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
    <div>
        <x-input-label for="motivo" value="Motivo" />
        <textarea id="motivo" name="motivo" required class="mt-1 w-full rounded-md border-gray-300"
            placeholder="Ej: Permiso autorizado por jefatura">{{ old('motivo', $permiso?->motivo) }}</textarea>
        @error('motivo')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="mt-1 w-full rounded-md border-gray-300" required>
            <option value="1" {{ old('estado', $permiso->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $permiso->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>

    {{-- ===================== NUEVO CAMPO: Ubicación Libre (Condicional) ===================== --}}
    <div id="campo_ubicacion_libre" class="hidden">
        <x-input-label for="ubicacion_libre" value="¿Ubicación libre?" />
        
        <select id="ubicacion_libre" name="ubicacion_libre" 
                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            
            {{-- Opción NO (0) --}}
            <option value="0" @selected($valorUbicacionLibre == 0)>No</option>
            
            {{-- Opción SÍ (1) --}}
            <option value="1" @selected($valorUbicacionLibre == 1)>Sí</option>

        </select>
        
        <p class="text-xs text-gray-500 mt-1">Si selecciona "Sí", no se validará el rango de metros.</p>
    </div>

    {{-- ===================== Campos dinámicos ===================== --}}
    <div id="campo_distancia" class="hidden">
        <x-input-label for="cantidad_mts" value="Distancia permitida (mts)" />
        <x-text-input id="cantidad_mts" name="cantidad_mts" type="number" min="1"
            value="{{ old('cantidad_mts', $permiso?->cantidad_mts) }}" class="mt-1 block w-full" />
    </div>

    <div id="campo_valor" class="hidden">
        <x-input-label for="valor" value="Valor del permiso (minutos)" />
        <x-text-input id="valor" name="valor" type="number" min="1" value="{{ old('valor', $permiso?->valor) }}"
            class="mt-1 block w-full" />
    </div>

    <div id="campo_fecha_inicio" class="hidden">
        <x-input-label for="fecha_inicio" value="Fecha inicio" />
        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date"
            value="{{ old('fecha_inicio', $permiso?->fecha_inicio) }}" 
            min="{{ !$esEditar ? now()->toDateString() : '' }}"
            class="mt-1 block w-full" />
    </div>
    <div id="campo_fecha_fin" class="hidden">
        <x-input-label for="fecha_fin" value="Fecha fin" />
        <x-text-input id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $permiso?->fecha_fin) }}"
            class="mt-1 block w-full" />
    </div>

    <div id="campo_dias" class="hidden">
        <x-input-label for="dias_activa" value="Días hábiles activos" />
        <x-text-input id="dias_activa" name="dias_activa" type="number" min="1"
            value="{{ old('dias_activa', $permiso?->dias_activa) }}" class="mt-1 block w-full" />
    </div>
</div>

<script>
    const ES_EDITAR = @json($esEditar);

function toggle(id, show) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden', !show);
    }

    function actualizarCampos() {
        const tipo = document.getElementById('tipo_permiso');
        if (!tipo || tipo.selectedIndex < 0) return;

        const opt = tipo.options[tipo.selectedIndex];
        const valTipo = tipo.value; 

        // 1. Determinar si es el tipo especial (value=1)
        // Nota: Asegúrate de que el value '1' corresponda al tipo que lleva esta lógica en tu DB
        let esTipoUno = (valTipo == 1); 
        toggle('campo_ubicacion_libre', esTipoUno);

        // 2. Lógica de Distancia
        let mostrarDistancia = (opt.dataset.distancia === '1');

        if (esTipoUno) {
            const ubicacionLibre = document.getElementById('ubicacion_libre').value;
            
            if (ubicacionLibre == '1') {
                // Si es SÍ, ocultamos distancia
                mostrarDistancia = false;
            } 
             // Si es NO, 'mostrarDistancia' se mantiene true (porque dataset.distancia es 1)
        }

        toggle('campo_distancia', mostrarDistancia);
        
        // Manejo de 'required'
        const inputDistancia = document.getElementById('cantidad_mts');
        if(inputDistancia) {
            if(mostrarDistancia) {
                inputDistancia.setAttribute('required', 'required');
            } else {
                inputDistancia.removeAttribute('required');
                // Opcional: limpiar valor si se oculta para evitar envíos sucios,
                // pero como es Editar, a veces es mejor dejarlo solo visualmente oculto.
            }
        }

        // Resto de campos
        toggle('campo_fecha_inicio', opt.dataset.fechas === '1');
        toggle('campo_fecha_fin', opt.dataset.fechas === '1');
        toggle('campo_dias', opt.dataset.dias === '1');
        toggle('campo_valor', opt.dataset.valor === '1');
    }

    function actualizarMinFechaFin() {
        const fechaInicio = $('#fecha_inicio').val();
        if (fechaInicio) {
            $('#fecha_fin').attr('min', fechaInicio);

            // Si fecha fin es menor que inicio, se limpia
            if ($('#fecha_fin').val() < fechaInicio) {
                $('#fecha_fin').val('');
            }
        } else {
            $('#fecha_fin').removeAttr('min');
        }
    }
    $('#fecha_inicio').on('change', actualizarMinFechaFin);

    $(document).ready(function () {

        // SOLO en create cargar empleados
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

        // Listener para cambio de tipo de permiso
        $('#tipo_permiso').on('change', actualizarCampos);
        
        // NUEVO: Listener para cambio en el select de ubicación libre
        $('#ubicacion_libre').on('change', actualizarCampos);

        actualizarCampos();
        actualizarMinFechaFin();

    });
</script>