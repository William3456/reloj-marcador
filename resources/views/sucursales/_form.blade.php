<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-input-label value="Nombre de sucursal" />
        <x-text-input type="text" value="{{ old('nombre', $sucursal->nombre ?? '') }}" name="nombre" class="mt-1 w-full"
            required />
    </div>

    <div>
        <x-input-label value="Dirección" />
        <textarea name="direccion" id="direccion"
            class="mt-1 w-full rounded-md border-gray-300" rows="2" required>{{ old('direccion', $sucursal->direccion ?? '') }}</textarea>
    </div>

    <div>
        <x-input-label value="Correo encargado" />
        <x-text-input type="email" name="correo_encargado"
            value="{{ old('correo_encargado', $sucursal->correo_encargado ?? '') }}" class="mt-1 w-full" required />
    </div>

    <div>
        <x-input-label value="Empresa" />
        <select name="id_empresa" class="mt-1 w-full rounded-md border-gray-300" required>
            @foreach ($empresas as $empresa)
                <option value="{{ $empresa->id }}" {{ old('id_empresa') == $empresa->id ? 'selected' : '' }}>
                    {{ $empresa->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label value="Horario asignado" />
        <select name="id_horario" class="mt-1 w-full rounded-md border-gray-300" required>
            <option value="">Seleccione...</option>
            @foreach ($horarios as $h)
                <option value="{{ $h->id }}" {{ old('id_horario', $sucursal->id_horario ?? '') == $h->id ? 'selected' : '' }}>
                    {{ $h->hora_ini }} - {{ $h->hora_fin }} ({{ $h->horas_laborales }}) 
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label value="Cantidad de empleados" />
        <x-text-input type="number" value="{{ old('cant_empleados', $sucursal->cant_empleados ?? '') }}"
            name="cant_empleados" class="mt-1 w-full" required />
    </div>

    <div>
        <x-input-label value="Rango de marcación (mts)" />
        <x-text-input type="number" value="{{ old('rango_marcacion_mts', $sucursal->rango_marcacion_mts ?? '') }}"
            name="rango_marcacion_mts" class="mt-1 w-full" required />
    </div>

    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="mt-1 w-full rounded-md border-gray-300" required>
            <option value="" {{ old('estado') === null ? 'selected' : '' }}>Seleccione...</option>

            @foreach ($estados as $estado)
                <option value="{{ $estado->id }}" {{ old('estado', $sucursal->estado ?? '' ) == (string) $estado->id ? 'selected' : '' }}>
                    {{ $estado->nombre_estado }}
                </option>
            @endforeach
        </select>

    </div>
    {{-- Días laborales --}}
    <div class="md:col-span-2">
        <x-input-label value="Días laborales" />

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 bg-gray-50 p-4 rounded-lg border">

            @foreach ($dias as $dia)
                <label class="flex items-center gap-2 p-2 bg-white rounded-md border hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" name="dias_laborales[]" value="{{ $dia }}"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded" {{ in_array($dia, old('dias_laborales', $sucursal->dias_laborales ?? [])) ? 'checked' : '' }}>
                    <span class="text-sm font-medium capitalize">{{ $dia }}</span>
                </label>
            @endforeach

        </div>
        @error('dias_laborales')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <x-mapa :lat="old('latitud', $sucursal->latitud ?? '')" :lng="old('longitud', $sucursal->longitud ?? '')" />
</div>