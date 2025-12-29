<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-input-label value="Sucursal" />
        <select id="sucursal" name="id_sucursal" class="w-full border-gray-300 rounded-md" required>
            <option value="">Seleccione…</option>
            @foreach ($sucursales as $s)
                <option value="{{ $s->id }}" {{ old('id_sucursal', $puesto->sucursal_id ?? '') == $s->id ? 'selected' : '' }}>
                    {{ $s->nombre }}
                </option>
            @endforeach
        </select>
        @error('id_sucursal') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>
    <div>
        <x-input-label value="Nombre puesto" />
        <x-text-input name="name" value="{{ old('name', $puesto->desc_puesto ?? '') }}" class="w-full" required />
        @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>
    <div>

        <x-input-label value="Estado" />
        <select name="estado" class="w-full border-gray-300 rounded-md" required>
            <option value="1" {{ old('estado', $puesto->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $puesto->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
        </select>
        @error('estado') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>
</div>