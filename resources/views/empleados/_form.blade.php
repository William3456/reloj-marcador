<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Nombres --}}
    <div>
        <x-input-label value="Nombres" />
        <x-text-input name="nombres" value="{{ old('nombres', $empleado->nombres ?? '') }}" class="w-full" />
        @error('nombres') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Apellidos --}}
    <div>
        <x-input-label value="Apellidos" />
        <x-text-input name="apellidos" value="{{ old('apellidos', $empleado->apellidos ?? '') }}" class="w-full" />
        @error('apellidos') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Documento --}}
    <div>
        <x-input-label value="Documento (DUI/NIT)" />
        <x-text-input name="documento" value="{{ old('documento', $empleado->documento ?? '') }}" class="w-full" />
        @error('documento') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Edad --}}
    <div>
        <x-input-label value="Edad" />
        <x-text-input type="number" name="edad" value="{{ old('edad', $empleado->edad ?? '') }}" class="w-full" />
        @error('edad') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Correo --}}
    <div>
        <x-input-label value="Correo" />
        <x-text-input type="email" name="correo" value="{{ old('correo', $empleado->correo ?? '') }}" class="w-full" />
        @error('correo') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Dirección --}}
    <div class="md:col-span-2">
        <x-input-label value="Dirección" />
        <textarea name="direccion" class="w-full border-gray-300 rounded-md">{{ old('direccion', $empleado->direccion ?? '') }}</textarea>
        @error('direccion') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Sucursal --}}
    <div>
        <x-input-label value="Sucursal" />
        <select id="sucursal" name="id_sucursal" class="w-full border-gray-300 rounded-md">
            <option value="">Seleccione…</option>
            @foreach ($sucursales as $s)
                <option value="{{ $s->id }}" {{ old('id_sucursal', $empleado->id_sucursal ?? '') == $s->id ? 'selected' : '' }}>
                    {{ $s->nombre }}
                </option>
            @endforeach
        </select>
        @error('id_sucursal') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Departamento --}}
    <div>
        <x-input-label value="Departamento" />
        <select id="departamento" name="id_depto" class="w-full border-gray-300 rounded-md">
            <option value="">Seleccione…</option>
        </select>
        @error('id_depto') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Puesto --}}
    <div>
        <x-input-label value="Puesto" />
        <select id="puesto" name="id_puesto" class="w-full border-gray-300 rounded-md">
            <option value="">Seleccione…</option>
        </select>
        @error('id_puesto') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Empresa --}}
    <div>
        <x-input-label value="Empresa" />
        <select name="id_empresa" class="w-full border-gray-300 rounded-md">
            @foreach ($empresas as $e)
                <option value="{{ $e->id }}" {{ old('id_empresa', $empleado->id_empresa ?? '') == $e->id ? 'selected' : '' }}>
                    {{ $e->nombre }}
                </option>
            @endforeach
        </select>
        @error('id_empresa') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Login --}}
    <div>
        <x-input-label value="¿Requiere login?" />
        <select name="login" class="w-full border-gray-300 rounded-md">
            <option value="0" {{ old('login', $empleado->login ?? '') == 0 ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('login', $empleado->login ?? '') == 1 ? 'selected' : '' }}>Sí</option>
        </select>
        @error('login') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Estado --}}
    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="w-full border-gray-300 rounded-md">
            <option value="1" {{ old('estado', $empleado->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $empleado->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
        </select>
        @error('estado') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

</div>
