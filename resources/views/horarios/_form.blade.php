<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Hora inicio -->
    <div>
        <x-input-label value="Hora de inicio (24h)" />
        <x-text-input type="time" name="hora_ini" id="hora_ini" value="{{ old('hora_ini', $horario->hora_ini ?? '') }}"
            class="mt-1 w-full" required />
    </div>

    <!-- Hora fin -->
    <div>
        <x-input-label value="Hora de fin (24h)" />
        <x-text-input type="time" name="hora_fin" id="hora_fin" value="{{ old('hora_fin', $horario->hora_fin ?? '') }}"
            class="mt-1 w-full" required />
    </div>

    <!-- Permitido marcación -->
    <div>
        <x-input-label value="Tipo de horario" />
        <select name="permitido_marcacion" id="permitido_marcacion" class="mt-1 w-full rounded-md border-gray-300"
            onchange="actualizarMensaje()" required>
            <option value="">Seleccione...</option>
            <option value="1" {{ old('permitido_marcacion', $horario->permitido_marcacion ?? '') == '1' ? 'selected' : '' }}>
                Horario de sucursal
            </option>
            <option value="0" {{ old('permitido_marcacion', $horario->permitido_marcacion ?? '') == '0' ? 'selected' : '' }}>
                Horario de trabajador
            </option>
        </select>
        <div class="mt-2">
            <small id="mensajeTipoHorario" class="text-cyan-600 text-sm font-medium" style="display: none;"></small>
        </div>
    </div>

    <!-- Estado -->
    <div>
        <x-input-label value="Estado" />
        <select name="estado" class="mt-1 w-full rounded-md border-gray-300" required>
            <option value="1" {{ old('estado', $horario->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $horario->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>

    <!-- Tolerancia -->
    <div>
        <x-input-label value="Tolerancia (minutos)" />
        <x-text-input type="number" min="0" name="tolerancia" id="tolerancia"
            value="{{ old('tolerancia', $horario->tolerancia ?? '') }}" class="mt-1 w-full" required />
    </div>

    <!-- Requiere salida -->
    <div>
        <x-input-label value="Requiere salida" />
        <select name="requiere_salida" id="requiere_salida" class="mt-1 w-full rounded-md border-gray-300" required>
            <option value="0" {{ old('requiere_salida', $horario->requiere_salida ?? '') == '0' ? 'selected' : '' }}>
                No requiere salida
            </option>
            <option value="1" {{ old('requiere_salida', $horario->requiere_salida ?? '') == '1' ? 'selected' : '' }}>
                Sí requiere salida
            </option>
        </select>
    </div>
    <div class="md:col-span-2">
        <x-input-label value="Días laborales (Inicios de)" />
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 bg-gray-50 p-4 rounded-lg border mt-1">
            @foreach ($dias as $dia)
                <label class="flex items-center gap-2 p-2 bg-white rounded-md border hover:bg-gray-100 cursor-pointer shadow-sm">
                    <input type="checkbox" name="dias[]" value="{{ $dia }}"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ in_array($dia, old('dias', $horario->dias ?? [])) ? 'checked' : '' }}>
                    <span class="text-sm font-medium capitalize select-none text-gray-700">{{ $dia }}</span>
                </label>
            @endforeach
        </div>
        @error('dias_laborales')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
    <!-- Turno (centrado y al final) -->
    <div class="col-md-4">

        <x-input-label value="Turno" />
        <input type="text" id="turno_txt" value="{{ old('turno_txt', $horario->turno_txt ?? '') }}" name="turno_txt"
            class="mt-1 w-full rounded-md border-gray-300 bg-gray-100" readonly>
        <input type="hidden" id="turno_id" value="{{ old('turno', $horario->turno ?? '') }}" name="turno">
    </div>
    <div>
        <x-input-label value="Horas de trabajo" />
        <input type="text" id="horas_trabajo" value="{{ old('horas_trabajo', $horario->horas_trabajo ?? '') }}"
            name="horas_trabajo" class="mt-1 w-full rounded-md border-gray-300 bg-gray-100" readonly>
    </div>
</div>
<script>
    $(document).ready(function () {

        let turnosBD = [];


        $.ajax({
            url: "/api/turnos",
            method: "GET",
            success: function (data) {
                turnosBD = data;
            }
        });

        function aMinutos(hora) {
            let [h, m] = hora.split(":").map(Number);
            return h * 60 + m;
        }

        function calcularTurno(min) {
            for (let t of turnosBD) {
                let ini = aMinutos(t.hora_ini);
                let fin = aMinutos(t.hora_fin);

                if (min >= ini && min <= fin) {
                    return t;
                }
            }
            return null;
        }

        function calcular() {

            let hIni = $("#hora_ini").val();
            let hFin = $("#hora_fin").val();

            if (!hIni || !hFin) {
                $("#turno_txt").val("");
                $("#turno_id").val("");
                $("#horas_trabajo").val("");
                return;
            }

            let iniMin = aMinutos(hIni);
            let finMin = aMinutos(hFin);


            let diff = finMin - iniMin;
            if (diff < 0) diff += 1440;

            let horas = Math.floor(diff / 60);
            let minutos = diff % 60;

            $("#horas_trabajo").val(`${horas}h ${String(minutos).padStart(2, '0')}m`);


            let turnosDetectados = [];

            for (let t = 0; t <= diff; t += 10) {
                let minutoReal = (iniMin + t) % 1440;
                let turno = calcularTurno(minutoReal);
                if (turno && !turnosDetectados.some(x => x.id === turno.id)) {
                    turnosDetectados.push(turno);
                }
            }


            if (turnosDetectados.length === 1) {
                console.log("Turnos detectados:", turnosDetectados);
                $("#turno_txt").val(turnosDetectados[0].nombre_turno);
                $("#turno_id").val(turnosDetectados[0].id);

            } else {
                console.log("Turnos detectados:", turnosDetectados);
                let nombres = turnosDetectados.map(t => t.nombre_turno).join(" - ");
                $("#turno_txt").val("Mixto (" + nombres + ")");
                $("#turno_id").val(0);

            }
        }

        // Eventos
        $("#hora_ini, #hora_fin").on("change", calcular);

        if ($("#hora_ini").val() && $("#hora_fin").val()) {
            let checkTurnos = setInterval(function () {
                if (turnosBD.length > 0) {
                    calcular();
                    clearInterval(checkTurnos);
                }
                actualizarMensaje()
            }, 100);
        }
        
    });


    function actualizarMensaje() {
        let selectPermitido = document.getElementById('permitido_marcacion');
        let mensaje = document.getElementById('mensajeTipoHorario');
        let tolerancia = document.getElementById('tolerancia');
        let requiere_salida = document.getElementById('requiere_salida');

        if (selectPermitido.value === "1") {
            // Sucursal → horario continuo
            mensaje.style.display = "block";
            mensaje.innerHTML = "Este horario debe ser continuo. Las sucursales no usan jornadas partidas.";
            tolerancia.style.backgroundColor = "";
            tolerancia.readOnly = false;

            // habilitar select
            requiere_salida.style.pointerEvents = "auto";
            requiere_salida.style.backgroundColor = "";
            requiere_salida.tabIndex = 0;

        } else if (selectPermitido.value === "0") {
            // Trabajador → horario partido
            mensaje.style.display = "block";
            mensaje.innerHTML = "Este horario puede ser partido. Puede asignar varios tramos para cubrir la jornada.";
            tolerancia.style.backgroundColor = "#f3f4f6";
            tolerancia.value = 0;
            tolerancia.readOnly = true;

            // deshabilitar visualmente (readonly real para select)
            requiere_salida.value = "0";
            requiere_salida.style.pointerEvents = "none";
            requiere_salida.style.backgroundColor = "#f3f4f6";
            requiere_salida.tabIndex = -1;

        } else {
            mensaje.style.display = "none";
            tolerancia.style.backgroundColor = "";
            tolerancia.readOnly = false;

            requiere_salida.style.pointerEvents = "auto";
            requiere_salida.style.backgroundColor = "";
            requiere_salida.tabIndex = 0;
        }
    }


</script>