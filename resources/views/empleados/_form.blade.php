<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Nombres --}}
    <div>
        <x-input-label value="Nombres" />
        <x-text-input name="nombres" value="{{ old('nombres', $empleado->nombres ?? '') }}" class="w-full" required />
    </div>

    {{-- Apellidos --}}
    <div>
        <x-input-label value="Apellidos" />
        <x-text-input name="apellidos" value="{{ old('apellidos', $empleado->apellidos ?? '') }}" class="w-full"
            required />
    </div>

    {{-- Documento --}}
    <div>
        <x-input-label value="Documento (DUI/NIT)" />
        <x-text-input name="documento" value="{{ old('documento', $empleado->documento ?? '') }}" class="w-full"
            required />
    </div>

    {{-- Edad --}}
    <div>
        <x-input-label value="Edad" />
        <x-text-input type="number" name="edad" value="{{ old('edad', $empleado->edad ?? '') }}" class="w-full"
            required />
    </div>

    {{-- Correo --}}
    <div>
        <x-input-label value="Correo" />
        <x-text-input type="email" name="correo" value="{{ old('correo', $empleado->correo ?? '') }}" class="w-full"
            required />
    </div>

    {{-- Dirección --}}
    <div>
        <x-input-label value="Dirección" />
        <textarea name="direccion" class="w-full border-gray-300 rounded-md text-sm"
            required>{{ old('direccion', $empleado->direccion ?? '') }}</textarea>
        @error('direccion')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>

    {{-- Sucursal --}}
    <div>
        <x-input-label value="Sucursal" />
        <select id="sucursal" name="id_sucursal" class="w-full border-gray-300 rounded-md" required>
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
        <select id="departamento" name="id_depto" class="w-full border-gray-300 rounded-md" required>
            <option value="">Seleccione…</option>
        </select>
        @error('id_depto') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Puesto --}}
    <div>
        <x-input-label value="Puesto" />
        <select id="puesto" name="id_puesto" class="w-full border-gray-300 rounded-md" required>
            <option value="">Seleccione…</option>
        </select>
        @error('id_puesto') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Empresa --}}
    <div>
        <x-input-label value="Empresa" />
        <select name="id_empresa" class="w-full border-gray-300 rounded-md" required>
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
        <select name="login" class="w-full border-gray-300 rounded-md" required>
            <option value="0" {{ old('login', $empleado->login ?? '') == 0 ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('login', $empleado->login ?? '') == 1 ? 'selected' : '' }}>Sí</option>
        </select>
        @error('login') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    {{-- Estado --}}
    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="w-full border-gray-300 rounded-md" required>
            <option value="1" {{ old('estado', $empleado->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $empleado->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
        </select>
        @error('estado') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>
</div>
<script>
    $(document).ready(function () {

        // Bloquear al inicio
        $('#departamento').prop('disabled', true);
        $('#puesto').prop('disabled', true);

        $('#sucursal').change(function () {
            let sucursalId = $(this).val();

            $('#departamento').html('<option>Cargando...</option>');
            $('#puesto').html('<option>Cargando...</option>');

            if (sucursalId) {
                // Habilitar selects
                $('#departamento').prop('disabled', false);
                $('#puesto').prop('disabled', false);

                $.ajax({
                    url: '/api/puestosDptosBySucId/' + sucursalId,
                    method: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        console.log("OK:", data);
                        const empleadoDepto = {{ $empleado->id_depto ?? 'null' }};
                        const empleadoPuesto = {{ $empleado->id_puesto ?? 'null' }};
                        // Departamentos
                        let dep = '<option value="">Seleccione</option>';
                        data.departamentos.forEach(d => {
                            dep += `<option value="${d.id}" ${d.id == empleadoDepto ? 'selected' : ''}>${d.nombre_depto}</option>`;
                        });
                        $('#departamento').html(dep);

                        // Puestos
                        let pst = '<option value="">Seleccione</option>';
                        data.puestos.forEach(p => {
                            pst += `<option value="${p.id}" ${p.id == empleadoPuesto ? 'selected' : ''}>${p.desc_puesto}</option>`;
                        });
                        $('#puesto').html(pst);
                    },
                    error: function (xhr, status, error) {
                        console.log("ERROR:");
                        console.log("Status:", status);
                        console.log("Código HTTP:", xhr.status);
                        console.log("Mensaje:", error);

                        if (xhr.responseJSON) {
                            console.log("Detalle Laravel:", xhr.responseJSON);
                        }

                        alertify.error('Error al cargar departamentos y puestos');

                        // En caso de error, bloquear de nuevo
                        $('#departamento').prop('disabled', true);
                        $('#puesto').prop('disabled', true);
                    }
                });
            } else {
                // Reset y bloquear
                $('#departamento').html('<option value="">Seleccione...</option>');
                $('#puesto').html('<option value="">Seleccione...</option>');

                $('#departamento').prop('disabled', true);
                $('#puesto').prop('disabled', true);
            }
        });
        if ($('#sucursal').val()) {
            $('#sucursal').trigger('change');
        }
    });

</script>