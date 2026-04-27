$(document).ready(function () {

    // Variables de estado
    let rows_selected = []; // Memoria de IDs seleccionados
    let totalCambios = 0;   // Contador de acciones (asignar/eliminar)

    // Actualizar la interfaz del contador de cambios
    function actualizarBadgeCambios() {
        let badge = $('#contadorCambios');
        let numSpan = $('#numCambios');

        numSpan.text(totalCambios + ' ');

        if (totalCambios > 0) {
            badge.removeClass('hidden').addClass('inline-flex animate-pulse');
            setTimeout(() => badge.removeClass('animate-pulse'), 1000);
        } else {
            badge.addClass('hidden').removeClass('inline-flex');
        }
    }

    // Función auxiliar para obtener los días de remoto actuales en la interfaz
    function obtenerDiasRemotoActuales(data) {
        if (data.remoto_accion === 'asignar') return data.remoto_pendiente || [];
        if (data.remoto_accion === 'eliminar') return [];
        if (data.trabajo_remoto && data.trabajo_remoto.dias) {
            try {
                let d = data.trabajo_remoto.dias;
                let parsed = (typeof d === 'string') ? JSON.parse(d) : d;
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) { return []; }
        }
        return [];
    }

    // Inicializar DataTable
    let tabla = new DataTable('#tablaTrabajadores', {
        data: [],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                width: '30px',
                render: function (data) {
                    return `<input type="checkbox" class="chkEmpleado cursor-pointer w-4 h-4 text-blue-600 rounded focus:ring-blue-500" value="${data.id}">`;
                }
            },
            { data: 'cod_trabajador', width: '80px', className: 'align-top font-mono text-xs' },
            { data: 'nombres', className: 'align-top font-medium text-gray-900' },
            { data: 'puesto.desc_puesto', className: 'align-top text-gray-500 text-xs' },
            {
                data: 'horarios',
                width: '250px',
                render: function (horarios, type, row, meta) {
                    let html = '<div class="flex flex-col gap-2">';

                    // Horarios presenciales
                    if (!horarios || horarios.length === 0) {
                        html += '<div class="p-2 text-gray-400 text-xs italic bg-gray-50 rounded text-center">Sin asignación</div>';
                    } else {
                        horarios.forEach((h, index) => {
                            let isNew = h.origen === 'Nuevo';
                            let containerClasses = isNew
                                ? 'bg-green-50 border border-green-200 border-l-4 border-l-green-500'
                                : 'bg-white border border-gray-200 border-l-4 border-l-blue-300';
                            let timeColor = isNew ? 'text-green-900' : 'text-gray-800';
                            let iconColor = isNew ? 'text-green-600' : 'text-gray-400';
                            let timeStr = `${h.hora_ini.substring(0, 5)} - ${h.hora_fin.substring(0, 5)}`;

                            let listaDias = h.dias;
                            if (typeof listaDias === 'string') {
                                try { listaDias = JSON.parse(listaDias); }
                                catch (error) { listaDias = listaDias.includes(',') ? listaDias.split(',').map(d => d.trim()) : [listaDias]; }
                            }
                            if (!Array.isArray(listaDias)) listaDias = [];

                            const diasTexto = listaDias
                                .map(dia => dia.charAt(0).toUpperCase() + dia.slice(1).toLowerCase().substring(0, 2))
                                .join(', ');

                            html += `
                            <div class="relative group flex items-start justify-between p-2 rounded shadow-sm transition-all hover:shadow-md ${containerClasses}">
                                <div class="flex flex-col">
                                    <span class="font-bold text-sm ${timeColor} font-mono">
                                        <i class="far fa-clock text-xs mr-1 ${iconColor}"></i>${timeStr}
                                    </span>
                                    <span class="text-[10px] text-gray-500 leading-tight mt-0.5">${diasTexto}</span>
                                </div>
                                <div class="flex flex-col items-end justify-between h-full pl-2 gap-1">
                                    ${isNew ? '<span class="text-[9px] font-bold uppercase tracking-wider text-green-700 bg-green-100 px-1 rounded">Nuevo</span>' : ''}
                                    <button type="button" class="btnEliminarHorario text-gray-400 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-white/50" data-index="${index}" title="Eliminar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>`;
                        });
                    }

                    // Trabajo remoto (Home office)
                    let diasRemoto = obtenerDiasRemotoActuales(row);

                    if (diasRemoto && diasRemoto.length > 0) {
                        html += `<div class="mt-2 pt-2 border-t border-dashed border-gray-200">`;
                        html += `<div class="flex flex-wrap gap-1.5 items-center">`;
                        html += `<span class="text-[10px] font-bold text-purple-600 mr-1"><i class="fa-solid fa-house-laptop"></i> Remoto:</span>`;
                        
                        diasRemoto.forEach(dia => {
                            let diaCorto = dia.charAt(0).toUpperCase() + dia.slice(1, 3).toLowerCase();
                            let esNuevoRemoto = row.remoto_accion === 'asignar'; 
                            
                            html += `
                            <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 text-[10px] font-bold px-1.5 py-0.5 rounded border ${esNuevoRemoto ? 'border-purple-400 shadow-sm' : 'border-purple-200'} transition-all hover:bg-purple-100">
                                ${diaCorto}
                                <button type="button" class="btnEliminarDiaRemoto text-purple-400 hover:text-red-600 focus:outline-none transition-colors" data-dia="${dia}" title="Quitar ${dia}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </span>`;
                        });

                        html += `</div></div>`;
                    }

                    html += '</div>';
                    return html;
                }
            }
        ],
        paging: true,
        searching: true,
        info: true,
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },

        rowCallback: function (row, data) {
            let rowId = data.id.toString();
            if ($.inArray(rowId, rows_selected) !== -1) {
                $(row).find('input[type="checkbox"]').prop('checked', true);
                $(row).addClass('bg-blue-50');
            } else {
                $(row).find('input[type="checkbox"]').prop('checked', false);
                $(row).removeClass('bg-blue-50');
            }
        }
    });

    // Asegurar estructura de datos de los registros
    tabla.rows().every(function () {
        let data = this.data();
        if (!data.horarios) { data.horarios = []; this.data(data); }
        if (!data.horarios_nuevos) data.horarios_nuevos = [];
        if (!data.horarios_eliminados) data.horarios_eliminados = [];
    });

    // Gestión de selección (checkboxes)
    function actualizarContadorSeleccionados() {
        let count = rows_selected.length;
        let span = $('#contadorSeleccionados');
        span.text(count + ' seleccionados');
        if (count > 0) {
            span.removeClass('bg-blue-100 text-blue-800').addClass('bg-blue-600 text-white shadow-md');
        } else {
            span.removeClass('bg-blue-600 text-white shadow-md').addClass('bg-blue-100 text-blue-800');
        }
    }

    $('#selectAll').on('click', function () {
        let isChecked = this.checked;
        let rows = tabla.rows({ 'search': 'applied' }).data();
        rows.each(function (data) {
            let id = data.id.toString();
            let index = $.inArray(id, rows_selected);
            if (isChecked && index === -1) rows_selected.push(id);
            else if (!isChecked && index !== -1) rows_selected.splice(index, 1);
        });
        tabla.draw(false);
        actualizarContadorSeleccionados();
    });

    $('#tablaTrabajadores tbody').on('click', 'input[type="checkbox"]', function (e) {
        let $row = $(this).closest('tr');
        let data = tabla.row($row).data();
        let id = data.id.toString();
        let index = $.inArray(id, rows_selected);

        if (this.checked && index === -1) rows_selected.push(id);
        else if (!this.checked && index !== -1) rows_selected.splice(index, 1);

        if (!this.checked) $('#selectAll').prop('checked', false);

        $row.toggleClass('bg-blue-50', this.checked);
        actualizarContadorSeleccionados();
        e.stopPropagation();
    });

    // Carga de datos por sucursal
    function cargarDatosSucursal() {
        let sucursalID = $('#sucursal').val();

        $('#dias, #horas, #tolerancia, #horario_laboral').text('-');
        rows_selected = [];
        $('#selectAll').prop('checked', false);
        actualizarContadorSeleccionados();

        totalCambios = 0;
        actualizarBadgeCambios();

        if (!sucursalID) {
            tabla.clear().draw();
            return;
        }

        $.get('/api/sucursal/details/' + sucursalID, function (data) {
            let htmlDiasSucursal = '<span class="text-gray-400 italic text-xs">-</span>';
            if (data.sucursal && data.sucursal.dias_laborales) {
                try {
                    let raw = data.sucursal.dias_laborales;
                    let arr = Array.isArray(raw) ? raw : JSON.parse(raw);
                    if (Array.isArray(arr) && arr.length > 0) {
                        let badges = arr.map(dia => `<span class="inline-block bg-blue-100 text-blue-800 text-[10px] font-medium px-2 py-0.5 rounded-full mr-1 mb-1 shadow-sm border border-blue-200">${dia.charAt(0).toUpperCase() + dia.slice(1).toLowerCase()}</span>`).join('');
                        htmlDiasSucursal = `<div class="flex flex-wrap content-start w-full">${badges}</div>`;
                    }
                } catch (e) { }
            }

            let htmlHoras = [], htmlTolerancia = [], htmlRangos = [], listaHidden = [];

            if (data.horarios && Array.isArray(data.horarios)) {
                data.horarios.forEach(function (item, index) {
                    let h = item.horario;
                    if (h) {
                        let separatorClass = (index < data.horarios.length - 1) ? 'border-b border-gray-100 pb-2 mb-2' : '';
                        let labelHtml = `<span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Horario ${index + 1}</span>`;

                        let diasTextoCorto = '';
                        if (h.dias) {
                            try {
                                let r = Array.isArray(h.dias) ? h.dias : JSON.parse(h.dias);
                                if (Array.isArray(r)) diasTextoCorto = r.map(d => d.charAt(0).toUpperCase() + d.slice(1, 3).toLowerCase()).join(', ');
                            } catch (e) { }
                        }

                        htmlHoras.push(`<div class="${separatorClass} flex flex-col justify-center h-full">${labelHtml}<span class="text-gray-700 font-medium">${h.horas_laborales ?? '-'}</span></div>`);
                        htmlTolerancia.push(`<div class="${separatorClass} flex flex-col justify-center h-full">${labelHtml}<span class="text-gray-600">${(h.tolerancia ?? '0') + ' min.'}</span></div>`);
                        htmlRangos.push(`<div class="${separatorClass} flex flex-col justify-center">${labelHtml}<span class="font-bold text-gray-800 text-sm">${h.hora_ini ?? '-'} - ${h.hora_fin ?? '-'}</span>${diasTextoCorto ? `<span class="text-[10px] text-gray-500 leading-tight mt-0.5">${diasTextoCorto}</span>` : ''}</div>`);

                        listaHidden.push({ inicio: h.hora_ini ?? '00:00', fin: h.hora_fin ?? '00:00', dias: h.dias });
                    }
                });
            }

            $('#dias').html(htmlDiasSucursal);
            $('#horas').html(htmlHoras.length > 0 ? htmlHoras.join('') : '-');
            $('#tolerancia').html(htmlTolerancia.length > 0 ? htmlTolerancia.join('') : '-');
            $('#horario_laboral').html(htmlRangos.length > 0 ? htmlRangos.join('') : '-');
            $('#horario_laboral_hidd').val(JSON.stringify(listaHidden));
        });

        $.ajax({
            url: '/api/empleados/sucursal/' + sucursalID,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                data.forEach(emp => {
                    emp.horarios_nuevos = [];
                    emp.horarios_eliminados = [];
                });
                tabla.clear().rows.add(data).draw();
            },
            error: function () { alertify.error('Error al cargar empleados'); }
        });
    }

    if ($('#sucursal').val()) cargarDatosSucursal();
    $('#sucursal').on('change', cargarDatosSucursal);

    // Gestión de horarios presenciales
    $('#btnAgregarHorario').click(function () {
        let select = $('#horario option:selected');
        let horarioID = select.val();

        if (!horarioID) { alertify.error('Selecciona un horario'); return; }

        let horarioObj = {
            id: horarioID,
            hora_ini: select.data('hora_ini'),
            hora_fin: select.data('hora_fin'),
            dias: select.data('dias'),
            origen: 'Nuevo'
        };

        if (rows_selected.length === 0) { alertify.error('Selecciona al menos un empleado'); return; }
        if (!horarioDentroDeSucursal(horarioObj.hora_ini, horarioObj.hora_fin, horarioObj.dias)) {
            alertify.error('El horario o el día no está permitido en esta sucursal');
            return;
        }

        let cambiosEnEstaAccion = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let rowId = data.id.toString();

            if ($.inArray(rowId, rows_selected) !== -1) {
                let hayCruce = data.horarios.some(h => horariosSeSobreponen(horarioObj.hora_ini, horarioObj.hora_fin, horarioObj.dias, h.hora_ini, h.hora_fin, h.dias));
                if (hayCruce) {
                    alertify.notify(`Cruce detectado para: ${data.cod_trabajador}`, 'warning', 5);
                    return;
                }

                let existe = data.horarios.some(h => h.id == horarioObj.id);
                if (!existe) {
                    let nuevoH = JSON.parse(JSON.stringify(horarioObj));
                    data.horarios.push(nuevoH);
                    if (!data.horarios_nuevos) data.horarios_nuevos = [];
                    data.horarios_nuevos.push(nuevoH);

                    totalCambios++;
                    cambiosEnEstaAccion++;
                    this.data(data).invalidate();
                }
            }
        });

        if (cambiosEnEstaAccion > 0) {
            alertify.success(`Horario asignado a ${cambiosEnEstaAccion} empleados`);
            tabla.draw(false);
            actualizarBadgeCambios();
        }
    });

    $('#tablaTrabajadores tbody').on('click', '.btnEliminarHorario', function () {
        let btn = $(this);
        let row = tabla.row(btn.closest('tr'));
        let data = row.data();
        let index = btn.data('index');
        let horario = data.horarios[index];

        if (!horario) return;

        alertify.confirm('Eliminar', `¿Quitar horario ${horario.hora_ini} - ${horario.hora_fin}?`,
            function () {
                if (!data.horarios_eliminados) data.horarios_eliminados = [];
                if (!data.horarios_nuevos) data.horarios_nuevos = [];

                if (horario.origen === 'Actual') data.horarios_eliminados.push(horario.id);
                if (horario.origen === 'Nuevo') data.horarios_nuevos = data.horarios_nuevos.filter(h => h.id != horario.id);

                data.horarios.splice(index, 1);
                totalCambios++;
                actualizarBadgeCambios();
                row.data(data).invalidate().draw(false);
                alertify.success('Horario removido');
            }, function () { }
        ).set('labels', { ok: 'Sí, eliminar', cancel: 'Cancelar' });
    });

    // Gestión de trabajo remoto (Home office)
    $('#btnAsignarRemoto').click(function (e) {
        e.preventDefault();

        let selectedDays = [];
        $('.dias-remoto-chk:checked').each(function () {
            selectedDays.push($(this).val());
        });

        if (rows_selected.length === 0) { alertify.error('Selecciona al menos un empleado en la tabla'); return; }
        if (selectedDays.length === 0) { alertify.error('Selecciona al menos un día para el Home office'); return; }

        let cambiosEnEstaAccion = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let rowId = data.id.toString();

            if ($.inArray(rowId, rows_selected) !== -1) {
                let diasActuales = obtenerDiasRemotoActuales(data);
                
                // Unimos días actuales con los seleccionados sin duplicados
                let nuevosDias = [...new Set([...diasActuales, ...selectedDays])].sort();
                let sortedActuales = diasActuales.slice().sort();

                if (JSON.stringify(nuevosDias) === JSON.stringify(sortedActuales)) {
                    return; 
                }

                data.remoto_pendiente = nuevosDias;
                data.remoto_accion = 'asignar';
                totalCambios++;
                cambiosEnEstaAccion++;
                this.data(data).invalidate();
            }
        });

        if (cambiosEnEstaAccion > 0) {
            alertify.success(`Días añadidos a ${cambiosEnEstaAccion} empleados`);
            tabla.draw(false);
            actualizarBadgeCambios();
        } else {
            alertify.warning('Los empleados seleccionados ya tenían asignados estos días.');
        }
    });

    // Eliminar día individual de Home office
    $('#tablaTrabajadores tbody').on('click', '.btnEliminarDiaRemoto', function (e) {
        e.stopPropagation(); 
        let btn = $(this);
        let row = tabla.row(btn.closest('tr'));
        let data = row.data();
        let diaAEliminar = btn.data('dia');

        let diasActuales = obtenerDiasRemotoActuales(data);
        let nuevosDias = diasActuales.filter(d => d !== diaAEliminar);

        if (nuevosDias.length === 0) {
            data.remoto_accion = 'eliminar';
            data.remoto_pendiente = [];
        } else {
            data.remoto_accion = 'asignar';
            data.remoto_pendiente = nuevosDias;
        }

        totalCambios++;
        actualizarBadgeCambios();
        row.data(data).invalidate().draw(false);
    });

    // Eliminar Home office masivamente
    $('#btnEliminarRemoto').click(function (e) {
        e.preventDefault();
        if (rows_selected.length === 0) { alertify.error('Selecciona al menos un empleado'); return; }

        let cambiosEnEstaAccion = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let rowId = data.id.toString();

            if ($.inArray(rowId, rows_selected) !== -1) {
                let diasActuales = obtenerDiasRemotoActuales(data);

                if (diasActuales.length === 0) return;

                data.remoto_pendiente = [];
                data.remoto_accion = 'eliminar';
                totalCambios++;
                cambiosEnEstaAccion++;
                this.data(data).invalidate();
            }
        });

        if (cambiosEnEstaAccion > 0) {
            alertify.success(`Home office removido a ${cambiosEnEstaAccion} empleados`);
            tabla.draw(false);
            actualizarBadgeCambios();
        } else {
            alertify.warning('Los empleados seleccionados no tenían Home office asignado.');
        }
    });

    // Funciones de validación
    function horariosSeSobreponen(ini1, fin1, dias1, ini2, fin2, dias2) {
        const clean = (t) => t ? t.substring(0, 5) : '';
        const horasChocan = clean(ini1) < clean(fin2) && clean(fin1) > clean(ini2);
        if (!horasChocan) return false;
        const d1 = normalizarDias(dias1);
        const d2 = normalizarDias(dias2);
        return d1.some(dia => d2.includes(dia));
    }

    function normalizarDias(diasInput) {
        if (!diasInput) return [];
        let lista = diasInput;
        if (typeof lista === 'string') {
            try { lista = JSON.parse(lista); } catch (e) { lista = lista.split(','); }
        }
        if (!Array.isArray(lista)) lista = [lista];
        return lista.map(d => String(d).trim());
    }

    function horarioDentroDeSucursal(horaIni, horaFin, diasSolicitados) {
        let rangoStr = $('#horario_laboral_hidd').val();
        if (!rangoStr || rangoStr === "undefined-undefined" || rangoStr.trim() === '' || rangoStr === '-' || rangoStr === '[]') return false;

        let reglasSucursal = [];
        try { reglasSucursal = JSON.parse(rangoStr); } catch (e) { return false; }

        let diasCheck = [];
        if (Array.isArray(diasSolicitados)) { diasCheck = diasSolicitados; }
        else if (typeof diasSolicitados === 'string') {
            try { diasCheck = JSON.parse(diasSolicitados); }
            catch (e) { diasCheck = diasSolicitados.split(',').map(d => d.trim()); }
        }
        if (!Array.isArray(diasCheck)) diasCheck = [diasCheck];

        const tm = (h) => {
            if (!h) return 0;
            let [hh, mm] = h.split(':').map(Number);
            return hh * 60 + mm;
        };

        const tIni = tm(horaIni);
        const tFin = tm(horaFin);

        return diasCheck.every(diaRequerido => {
            return reglasSucursal.some(regla => {
                let diasRegla = [];
                if (Array.isArray(regla.dias)) { diasRegla = regla.dias; }
                else {
                    try { diasRegla = JSON.parse(regla.dias); }
                    catch (e) { diasRegla = regla.dias.split(',').map(d => d.trim()); }
                }
                if (!diasRegla.includes(diaRequerido)) return false;
                return tIni >= tm(regla.inicio) && tFin <= tm(regla.fin);
            });
        });
    }

    // Envío del formulario masivo
    $('#formHorario').on('submit', function (e) {
        e.preventDefault();
        $('.dinamico').remove();
        let form = this;
        let itemsProcesados = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let empID = data.id;

            let nuevos = data.horarios_nuevos || [];
            let eliminados = data.horarios_eliminados || [];
            let rAccion = data.remoto_accion; 

            if (nuevos.length > 0 || eliminados.length > 0 || rAccion) {
                itemsProcesados++;

                nuevos.forEach(h => {
                    $('<input>', { type: 'hidden', name: `nuevos[${empID}][]`, value: h.id, class: 'dinamico' }).appendTo(form);
                });
                eliminados.forEach(idH => {
                    $('<input>', { type: 'hidden', name: `eliminados[${empID}][]`, value: idH, class: 'dinamico' }).appendTo(form);
                });

                if (rAccion) {
                    $('<input>', { type: 'hidden', name: `remoto_accion[${empID}]`, value: rAccion, class: 'dinamico' }).appendTo(form);

                    if (rAccion === 'asignar') {
                        data.remoto_pendiente.forEach(dia => {
                            $('<input>', { type: 'hidden', name: `remoto_dias[${empID}][]`, value: dia, class: 'dinamico' }).appendTo(form);
                        });
                    }
                }
            }
        });

        if (itemsProcesados === 0) { alertify.error('No hay cambios pendientes de horarios o remoto'); return; }

        alertify.confirm('Confirmar asignaciones', `Se procesarán cambios masivos para ${itemsProcesados} empleados. ¿Continuar?`,
            function () { form.submit(); },
            function () { }
        ).set('labels', { ok: 'Sí, guardar', cancel: 'Cancelar' });
    });
});