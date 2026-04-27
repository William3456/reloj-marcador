document.addEventListener('DOMContentLoaded', function () {
    // Referencias DOM
    const selectSucursal = document.getElementById('sucursal');
    const selectHorario = document.getElementById('horario');
    const btnAgregar = document.getElementById('btnAgregar');
    const btnGuardar = document.getElementById('btnGuardar');
    const tablaBody = document.getElementById('tabla-body');
    const rowEmpty = document.getElementById('row-empty');
    const infoDiasDiv = document.getElementById('info-dias-sucursal');
    const contadorBadge = document.getElementById('contador-horarios');
    const cambiosBadge = document.getElementById('badge-cambios');

    // Almacenamiento de datos
    let horariosIds = new Set();        // Estado actual (IDs en la tabla)
    let initialHorariosIds = new Set(); // Estado inicial (IDs de la base de datos)
    let horariosDetallados = [];        // Para validación de cruces

    // Evento: cambio de sucursal
    selectSucursal.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const sucursalId = this.value;

        // Resetear interfaz y datos
        tablaBody.innerHTML = '';
        if (rowEmpty) tablaBody.appendChild(rowEmpty);

        horariosIds.clear();
        initialHorariosIds.clear(); 
        horariosDetallados = [];
        actualizarEstadoInterfaz();

        if (!sucursalId) {
            infoDiasDiv.innerHTML = '<span class="italic text-gray-400">Selecciona una sucursal...</span>';
            btnAgregar.disabled = true;
            btnGuardar.disabled = true;
            return;
        }

        // Habilitar controles
        btnAgregar.disabled = false;

        // Renderizar días
        const diasJson = selectedOption.getAttribute('data-dias');
        const diasSucursal = parseJsonSafe(diasJson);
        renderBadges(diasSucursal, infoDiasDiv, 'blue');

        // Cargar horarios existentes
        cargarHorariosExistentes(sucursalId);
    });

    // Evento: agregar horario
    btnAgregar.addEventListener('click', function () {
        // Validaciones básicas
        if (!selectSucursal.value) { alertify.error('Selecciona una sucursal.'); return; }
        if (!selectHorario.value) { alertify.error('Selecciona un horario.'); return; }

        const sucursalOption = selectSucursal.options[selectSucursal.selectedIndex];
        const horarioOption = selectHorario.options[selectHorario.selectedIndex];
        const horarioId = String(selectHorario.value);

        // Validación de duplicados
        if (horariosIds.has(horarioId)) {
            alertify.error('Este horario ya está en la lista.');
            return;
        }

        // Validaciones de negocio (días y cruces)
        const sucursalDias = parseJsonSafe(sucursalOption.getAttribute('data-dias'));
        const horarioDias = parseJsonSafe(horarioOption.getAttribute('data-dias'));
        const inicioStr = horarioOption.getAttribute('data-inicio');
        const finStr = horarioOption.getAttribute('data-fin');
        let minInicio = timeToMinutes(inicioStr);
        let minFin = timeToMinutes(finStr);
        if (minFin <= minInicio) minFin += 1440;

        // Días laborales
        const diasInvalidos = horarioDias.filter(dia => !sucursalDias.includes(dia));
        if (diasInvalidos.length > 0) {
            alertify.error(`El horario incluye días no laborales: ${diasInvalidos.join(', ')}`);
            return;
        }

        // Cruce de horarios
        for (let existente of horariosDetallados) {
            const diasComunes = existente.dias.filter(dia => horarioDias.includes(dia));
            if (diasComunes.length > 0) {
                if (minInicio < existente.fin && minFin > existente.inicio) {
                    alertify.error(`Cruce detectado con: ${existente.etiqueta} (${diasComunes.join(', ')})`);
                    return;
                }
            }
        }

        // Agregar a la lista
        horariosIds.add(horarioId);
        horariosDetallados.push({
            id: horarioId,
            dias: horarioDias,
            inicio: minInicio,
            fin: minFin,
            etiqueta: `${inicioStr} - ${finStr}`
        });

        agregarFilaTabla(horarioOption, horarioDias, horarioId);
        actualizarEstadoInterfaz();
        alertify.success('Horario agregado.');
        selectHorario.value = "";
    });

    // Funciones de carga y estado
    function cargarHorariosExistentes(id) {
        // Deshabilitar guardar mientras carga
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Cargando...';

        fetch(`/api/horarios-sucursal/${id}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(h => {
                    const diasArray = Array.isArray(h.dias) ? h.dias : parseJsonSafe(h.dias);
                    const strId = String(h.id);

                    const dummyOption = document.createElement('option');
                    dummyOption.value = h.id;
                    dummyOption.setAttribute('data-turno', h.turno_txt || 'S/T');
                    dummyOption.setAttribute('data-inicio', h.hora_ini);
                    dummyOption.setAttribute('data-fin', h.hora_fin);

                    let minInicio = timeToMinutes(h.hora_ini);
                    let minFin = timeToMinutes(h.hora_fin);
                    if (minFin <= minInicio) minFin += 1440;

                    // Guardar estado actual e inicial
                    horariosIds.add(strId);
                    initialHorariosIds.add(strId); 

                    horariosDetallados.push({
                        id: strId,
                        dias: diasArray,
                        inicio: minInicio,
                        fin: minFin,
                        etiqueta: `${h.hora_ini} - ${h.hora_fin}`
                    });

                    agregarFilaTabla(dummyOption, diasArray, strId);
                });

                // Restaurar botón guardar
                btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar asignación';
                actualizarEstadoInterfaz();
            })
            .catch(error => {
                console.error(error);
                alertify.error('Error al cargar horarios.');
                btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar asignación';
                btnGuardar.disabled = false;
            });
    }

    function actualizarEstadoInterfaz() {
        const totalActual = horariosIds.size;

        // Calcular cambios
        let agregados = 0;
        let eliminados = 0;

        horariosIds.forEach(id => {
            if (!initialHorariosIds.has(id)) agregados++;
        });

        initialHorariosIds.forEach(id => {
            if (!horariosIds.has(id)) eliminados++;
        });

        const totalCambios = agregados + eliminados;

        // Actualizar indicadores
        contadorBadge.textContent = `${totalActual} horarios`;

        if (totalCambios > 0) {
            cambiosBadge.innerHTML = `<i class="fas fa-pen mr-1"></i> ${totalCambios} cambios`;
            cambiosBadge.classList.remove('hidden');
        } else {
            cambiosBadge.classList.add('hidden');
        }

        // Manejo del botón guardar
        if (selectSucursal.value) {
            btnGuardar.disabled = false;
            btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btnGuardar.disabled = true;
            btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Funciones de interfaz
    function agregarFilaTabla(option, diasArray, id) {
        const emptyRow = document.getElementById('row-empty');
        if (emptyRow) emptyRow.remove();

        const turno = option.getAttribute('data-turno');
        const inicio = option.getAttribute('data-inicio');
        const fin = option.getAttribute('data-fin');

        const tr = document.createElement('tr');
        tr.className = "hover:bg-gray-50 transition-colors border-b border-gray-100 animate__animated animate__fadeIn";
        tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${turno}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${inicio} - ${fin}</td>
                <td class="px-6 py-4 text-sm text-gray-500"><div class="flex flex-wrap gap-1" id="badge-container-${id}"></div></td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <input type="hidden" name="horarios_ids[]" value="${id}">
                    <button type="button" class="btn-eliminar text-red-600 hover:text-red-900 p-2"><i class="fas fa-trash"></i></button>
                </td>
            `;

        const badgeContainer = tr.querySelector(`#badge-container-${id}`);
        renderBadges(diasArray, badgeContainer, 'gray');
        tablaBody.appendChild(tr);

        tr.querySelector('.btn-eliminar').addEventListener('click', function () {
            tr.remove();
            horariosIds.delete(id);
            horariosDetallados = horariosDetallados.filter(h => h.id !== id);

            if (tablaBody.children.length === 0) {
                const newEmpty = document.createElement('tr');
                newEmpty.id = 'row-empty';
                newEmpty.innerHTML = '<td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">Selecciona una sucursal y agrega horarios para comenzar.</td>';
                tablaBody.appendChild(newEmpty);
            }

            actualizarEstadoInterfaz();
        });
    }

    function renderBadges(datos, contenedor, color) {
        contenedor.innerHTML = '';
        if (!datos) return;
        let lista = Array.isArray(datos) ? datos : Object.values(datos);
        const colorClass = color === 'blue' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-gray-100 text-gray-800 border-gray-200';
        lista.forEach(dia => {
            contenedor.innerHTML += `<span class="${colorClass} text-xs font-semibold px-2 py-0.5 rounded border mr-1 mb-1 inline-block">${dia}</span>`;
        });
    }

    function timeToMinutes(str) {
        if (!str) return 0;
        const [h, m] = str.split(':').map(Number);
        return (h * 60) + m;
    }

    function parseJsonSafe(str) {
        try { const res = JSON.parse(str); return (res && typeof res === 'object') ? res : []; } catch { return []; }
    }
});