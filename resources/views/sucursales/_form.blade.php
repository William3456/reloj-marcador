<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- 1. Nombre --}}
    <div>
        <x-input-label value="Nombre de sucursal" />
        <x-text-input type="text" value="{{ old('nombre', $sucursal->nombre ?? '') }}" name="nombre" class="mt-1 w-full" required />
    </div>

    {{-- 2. Dirección --}}
    <div>
        <x-input-label value="Dirección" />
        <textarea name="direccion" id="direccion" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="2" required>{{ old('direccion', $sucursal->direccion ?? '') }}</textarea>
    </div>

    {{-- 3. Correo --}}
    <div>
        <x-input-label value="Correo encargado" />
        <x-text-input type="email" name="correo_encargado" value="{{ old('correo_encargado', $sucursal->correo_encargado ?? '') }}" class="mt-1 w-full" required />
    </div>

    {{-- 4. Teléfono --}}
    <div>
        <x-input-label value="Teléfono" />
        <x-text-input id="telefono" type="text" name="telefono" maxlength="9" value="{{ old('telefono', $sucursal->telefono ?? '') }}" class="mt-1 w-full" required />
    </div>

    {{-- 5. Empresa --}}
    <div>
        <x-input-label value="Empresa" />
        <select name="id_empresa" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @foreach ($empresas as $empresa)
                <option value="{{ $empresa->id }}" {{ old('id_empresa') == $empresa->id ? 'selected' : '' }}>
                    {{ $empresa->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- 6. Horario --}}
    <div>
        <x-input-label value="Horario asignado" />
        <select name="id_horario" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">Seleccione...</option>
            @foreach ($horarios as $h)
                <option value="{{ $h->id }}" {{ old('id_horario', $sucursal->id_horario ?? '') == $h->id ? 'selected' : '' }}>
                    {{ $h->hora_ini }} - {{ $h->hora_fin }} ({{ $h->horas_laborales }}) 
                </option>
            @endforeach
        </select>
    </div>
    
    {{-- AQUI EMPIEZA LA CORRECCIÓN DE LAYOUT --}}

    {{-- 7. RANGO DE MARCACIÓN (Tooltip Flotante) --}}
    {{-- Eliminamos el div wrapper extra para que este ocupe su propia celda en el grid --}}
{{-- 7. RANGO DE MARCACIÓN --}}
<div>
        <x-input-label value="Rango de marcación (mts)" />
        
        <x-text-input type="number" value="{{ old('rango_marcacion_mts', $sucursal->rango_marcacion_mts ?? '') }}"
            name="rango_marcacion_mts" class="w-full mt-1" placeholder="Ej: 20" required />

        {{-- Ayuda estática (estilo similar al de GPS pero sin desplegar) --}}
        <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    <strong>Zona Permitida:</strong> Define qué tan lejos de la coordenada exacta permites marcar (independientemente de la señal GPS).
                </p>
            </div>
        </div>
    </div>

    {{-- 8. MARGEN DE ERROR GPS (Ayuda Desplegable) --}}
    <div>
        <div class="flex items-center justify-between mb-1">
            <x-input-label value="Rango de error de GPS (mts)" />
        </div>

        <x-text-input type="number" value="{{ old('margen_error_gps_mts', $sucursal->margen_error_gps_mts ?? '') }}"
            name="margen_error_gps_mts" class="w-full mt-1" placeholder="Ej 30" required />

        {{-- Detalles desplegables (Acordeón) --}}
        <details class="mt-2 group text-sm text-gray-600 bg-blue-50 rounded-lg border border-blue-100 open:bg-white open:shadow-sm transition-all duration-300 relative z-10">
    <summary class="cursor-pointer font-medium text-blue-600 p-2 hover:bg-blue-100 rounded-t-lg select-none flex items-center gap-2 text-xs">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        <span>¿Qué margen de error elegir?</span>
    </summary>
    
    <div class="p-3 pt-0 border-t border-blue-100 group-open:pt-3">
        <p class="mb-3 text-xs text-gray-500 text-justify leading-relaxed">
            Los celulares pierden precisión bajo techo o entre muros gruesos. Este margen es una <strong>tolerancia técnica</strong> para evitar que el sistema rechace marcaciones válidas cuando la señal del GPS "rebota".
        </p>

        {{-- Recomendación destacada --}}
        <div class="mb-3 p-2 bg-blue-100 text-blue-800 rounded text-center text-xs font-bold border border-blue-200">
            💡 ¿No está seguro? Un valor estándar seguro es 30.
        </div>

        <div class="overflow-hidden rounded border border-gray-200">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                    <tr>
                        <th class="px-2 py-1">Entorno</th>
                        <th class="px-2 py-1 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-2 py-1">Aire libre / Parqueo</td>
                        <td class="px-2 py-1 font-mono text-blue-600 text-right">5 - 10</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1">Oficina con ventanas</td>
                        <td class="px-2 py-1 font-mono text-blue-600 text-right">15 - 20</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 bg-yellow-50 font-bold text-yellow-800">Edificio cerrado / Bodega</td>
                        <td class="px-2 py-1 font-mono text-blue-600 text-right bg-yellow-50 font-bold">30</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 bg-orange-50">Centro Comercial / Sótano</td>
                        <td class="px-2 py-1 font-mono text-blue-600 text-right bg-orange-50">50 - 80</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</details>
    </div>
    {{-- 9. Cantidad de Empleados --}}
    <div>
        <x-input-label value="Cantidad de empleados" />
        <x-text-input type="number" value="{{ old('cant_empleados', $sucursal->cant_empleados ?? '') }}" name="cant_empleados" class="mt-1 w-full" required />
    </div>

    {{-- 10. Estado --}}
    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="" {{ old('estado') === null ? 'selected' : '' }}>Seleccione...</option>
            @foreach ($estados as $estado)
                <option value="{{ $estado->id }}" {{ old('estado', $sucursal->estado ?? '' ) == (string) $estado->id ? 'selected' : '' }}>
                    {{ $estado->nombre_estado }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- 11. Días laborales (Ocupa 2 columnas) --}}
    <div class="md:col-span-2">
        <x-input-label value="Días laborales" />
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 bg-gray-50 p-4 rounded-lg border mt-1">
            @foreach ($dias as $dia)
                <label class="flex items-center gap-2 p-2 bg-white rounded-md border hover:bg-gray-100 cursor-pointer shadow-sm">
                    <input type="checkbox" name="dias_laborales[]" value="{{ $dia }}"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ in_array($dia, old('dias_laborales', $sucursal->dias_laborales ?? [])) ? 'checked' : '' }}>
                    <span class="text-sm font-medium capitalize select-none text-gray-700">{{ $dia }}</span>
                </label>
            @endforeach
        </div>
        @error('dias_laborales')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 12. Mapa (Ocupa 2 columnas) --}}
    <div class="md:col-span-2">
        <x-mapa :lat="old('latitud', $sucursal->latitud ?? '')" :lng="old('longitud', $sucursal->longitud ?? '')" />
    </div>

</div>

@push('scripts')
<script>
    document.getElementById('telefono').addEventListener('input', function (e) {
        let valor = e.target.value.replace(/\D/g, ''); // solo números
        if (valor.length > 4) {
            valor = valor.slice(0, 4) + ' ' + valor.slice(4, 8);
        }
        e.target.value = valor;
    });
</script>
@endpush