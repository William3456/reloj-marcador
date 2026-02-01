<x-app-layout title="Asignar Horarios a Sucursal">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asignación de Horarios por Sucursal') }}
        </h2>
    </x-slot>

    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Mensajes de feedback --}}
            @if (session('success') || session('error'))
                <div
                    class="rounded-lg shadow-sm p-4 mb-4 border-l-4 {{ session('success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700' }}">
                    <p class="font-bold"><i class="fas {{ session('success') ? 'fa-check' : 'fa-exclamation' }}-circle"></i>
                        {{ session('success') ?? session('error') }}</p>
                </div>
            @endif

            <form id="formAsignacionSucursal" action="{{ route('horario_sucursal.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 2. COLUMNA IZQUIERDA: CONTROLES --}}
                    <div class="lg:col-span-1 space-y-6">

                        {{-- CARD SELECCIÓN --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-sliders-h mr-2 text-blue-500"></i> Configuración
                                </h3>
                            </div>

                            <div class="p-5 space-y-5">
                                {{-- Select Sucursal --}}
                                <div>
                                    <label for="sucursal"
                                        class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal *</label>
                                    <div class="relative">
                                        <select id="sucursal" name="id_sucursal"
                                            class="pl-2 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">Seleccione una sucursal...</option>
                                            @foreach ($sucursales as $s)
                                                <option value="{{ $s->id }}" data-dias='@json($s->dias_laborales)'>
                                                    {{ $s->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Select Horario --}}
                                <div>
                                    <label for="horario"
                                        class="block text-xs font-bold text-gray-500 uppercase mb-1">Horario Disponible
                                        *</label>
                                    <div class="relative">
                                        <select id="horario"
                                            class="pl-2 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">Seleccione un horario...</option>
                                            @foreach ($horarios as $h)
                                                <option value="{{ $h->id }}" data-inicio="{{ $h->hora_ini }}"
                                                    data-fin="{{ $h->hora_fin }}" data-turno="{{ $h->turno_txt }}"
                                                    data-dias='@json($h->dias)'>
                                                    {{ $h->hora_ini }} - {{ $h->hora_fin }}
                                                    ({{ implode(', ', $h->dias ?? []) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p id="error-validacion" class="text-red-500 text-xs mt-2 hidden"></p>
                                </div>

                                <button type="button" id="btnAgregar" disabled
                                    class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all">
                                    <i class="fas fa-plus-circle mr-2"></i> Agregar a la Lista
                                </button>
                            </div>
                        </div>

                        {{-- CARD INFO SUCURSAL --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i> Días Laborales Sucursal
                                </h3>
                            </div>
                            <div class="p-4">
                                <div id="info-dias-sucursal" class="flex flex-wrap gap-2 text-sm text-gray-600">
                                    <span class="italic text-gray-400">Seleccione una sucursal para ver sus
                                        días...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. COLUMNA DERECHA: TABLA DE HORARIOS --}}
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 h-full flex flex-col">
                            <div
                                class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-base font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-clock text-gray-400 mr-2"></i> Horarios a Asignar
                                </h3>

                                <div class="flex gap-2">
                                    {{-- Contador de Cambios (NUEVO) --}}
                                    <span id="badge-cambios"
                                        class="hidden bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold px-2.5 py-0.5 rounded-full animate-pulse">
                                        <i class="fas fa-pen mr-1"></i> 0 cambios
                                    </span>

                                    {{-- Contador Total --}}
                                    <span id="contador-horarios"
                                        class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-200">
                                        0 horarios
                                    </span>
                                </div>
                            </div>

                            <div class="p-0 flex-grow overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Turno</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Entrada / Salida</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Días Aplicables</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">
                                                Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body" class="bg-white divide-y divide-gray-200">
                                        <tr id="row-empty">
                                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">
                                                Seleccione una sucursal y agregue horarios para comenzar.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Footer de Acción --}}
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                                <button type="submit" id="btnGuardar" disabled
                                    class="px-6 py-3 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-lg transform hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-save mr-2"></i> Guardar Asignación
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // REFERENCIAS DOM
                const selectSucursal = document.getElementById('sucursal');
                const selectHorario = document.getElementById('horario');
                const btnAgregar = document.getElementById('btnAgregar');
                const btnGuardar = document.getElementById('btnGuardar');
                const tablaBody = document.getElementById('tabla-body');
                const rowEmpty = document.getElementById('row-empty');
                const infoDiasDiv = document.getElementById('info-dias-sucursal');
                const contadorBadge = document.getElementById('contador-horarios');
                const cambiosBadge = document.getElementById('badge-cambios');

                // ALMACENAMIENTO DE DATOS
                let horariosIds = new Set();        // Estado ACTUAL (IDs en la tabla)
                let initialHorariosIds = new Set(); // Estado INICIAL (IDs que vinieron de la BD)
                let horariosDetallados = [];        // Para validación de cruces

                // ------------------------------------------------------
                // 1. EVENTO: Cambio de Sucursal
                // ------------------------------------------------------
                selectSucursal.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const sucursalId = this.value;

                    // Resetear interfaz y datos
                    tablaBody.innerHTML = '';
                    if (rowEmpty) tablaBody.appendChild(rowEmpty);

                    horariosIds.clear();
                    initialHorariosIds.clear(); // Limpiamos el estado inicial también
                    horariosDetallados = [];
                    actualizarEstadoInterfaz(); // Actualiza contadores y botones

                    if (!sucursalId) {
                        infoDiasDiv.innerHTML = '<span class="italic text-gray-400">Seleccione una sucursal...</span>';
                        btnAgregar.disabled = true;
                        btnGuardar.disabled = true; // Sin sucursal, no se guarda nada
                        return;
                    }

                    // Si hay sucursal, habilitamos controles
                    btnAgregar.disabled = false;

                    // Renderizar días
                    const diasJson = selectedOption.getAttribute('data-dias');
                    const diasSucursal = parseJsonSafe(diasJson);
                    renderBadges(diasSucursal, infoDiasDiv, 'blue');

                    // Cargar horarios existentes
                    cargarHorariosExistentes(sucursalId);
                });

                // ------------------------------------------------------
                // 2. EVENTO: Click Agregar Horario
                // ------------------------------------------------------
                btnAgregar.addEventListener('click', function () {
                    // Validaciones básicas
                    if (!selectSucursal.value) { alertify.error('Seleccione una sucursal.'); return; }
                    if (!selectHorario.value) { alertify.error('Seleccione un horario.'); return; }

                    const sucursalOption = selectSucursal.options[selectSucursal.selectedIndex];
                    const horarioOption = selectHorario.options[selectHorario.selectedIndex];
                    const horarioId = String(selectHorario.value);

                    // Validación duplicados
                    if (horariosIds.has(horarioId)) {
                        alertify.error('Este horario ya está agregado.');
                        return;
                    }

                    // Validaciones de negocio (Días y Cruces)
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
                        alertify.error(`Horario incluye días no laborales: ${diasInvalidos.join(', ')}`);
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

                    // AGREGAR
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

                // ------------------------------------------------------
                // FUNCIONES DE CARGA Y ESTADO
                // ------------------------------------------------------

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

                                // Crear option temporal para reutilizar la función de pintar tabla
                                const dummyOption = document.createElement('option');
                                dummyOption.value = h.id;
                                dummyOption.setAttribute('data-turno', h.turno_txt || 'S/T');
                                dummyOption.setAttribute('data-inicio', h.hora_ini);
                                dummyOption.setAttribute('data-fin', h.hora_fin);

                                let minInicio = timeToMinutes(h.hora_ini);
                                let minFin = timeToMinutes(h.hora_fin);
                                if (minFin <= minInicio) minFin += 1440;

                                // Guardar en estado ACTUAL y en estado INICIAL
                                horariosIds.add(strId);
                                initialHorariosIds.add(strId); // <--- CLAVE PARA EL CONTADOR DE CAMBIOS

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
                            btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Asignación';
                            actualizarEstadoInterfaz();
                        })
                        .catch(error => {
                            console.error(error);
                            alertify.error('Error al cargar horarios.');
                            btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Asignación';
                            btnGuardar.disabled = false; // Permitir intentar guardar aunque falle carga (opcional)
                        });
                }

                // Esta función reemplaza a 'actualizarContador' y maneja toda la lógica visual
                function actualizarEstadoInterfaz() {
                    const totalActual = horariosIds.size;

                    // 1. Calcular Cambios
                    let agregados = 0;
                    let eliminados = 0;

                    // Contar cuántos hay ahora que no estaban al inicio (Agregados)
                    horariosIds.forEach(id => {
                        if (!initialHorariosIds.has(id)) agregados++;
                    });

                    // Contar cuántos habían al inicio que no están ahora (Eliminados)
                    initialHorariosIds.forEach(id => {
                        if (!horariosIds.has(id)) eliminados++;
                    });

                    const totalCambios = agregados + eliminados;

                    // 2. Actualizar Badges
                    contadorBadge.textContent = `${totalActual} horarios`;

                    if (totalCambios > 0) {
                        cambiosBadge.innerHTML = `<i class="fas fa-pen mr-1"></i> ${totalCambios} cambios`;
                        cambiosBadge.classList.remove('hidden');
                    } else {
                        cambiosBadge.classList.add('hidden');
                    }

                    // 3. Manejo del Botón Guardar
                    // REGLA: Si hay sucursal seleccionada, SIEMPRE permitir guardar.
                    // Esto permite guardar una lista vacía (eliminar todos).
                    if (selectSucursal.value) {
                        btnGuardar.disabled = false;
                        btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        btnGuardar.disabled = true;
                        btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }

                // ------------------------------------------------------
                // FUNCIONES UI
                // ------------------------------------------------------
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

                        // Si queda vacío, mostrar mensaje
                        if (tablaBody.children.length === 0) {
                            const newEmpty = document.createElement('tr');
                            newEmpty.id = 'row-empty';
                            newEmpty.innerHTML = '<td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">Seleccione una sucursal y agregue horarios.</td>';
                            tablaBody.appendChild(newEmpty);
                        }

                        actualizarEstadoInterfaz(); // Recalcular cambios
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
        </script>
    @endpush
</x-app-layout>