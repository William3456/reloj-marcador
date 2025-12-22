
$(document).ready(function () {

    // Inicializas la tabla VACÍA desde el inicio
    let tabla = new DataTable('#tablaTrabajadores', {
        data: [],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data) {
                    return `<input type="checkbox" class="chkEmpleado" value="${data.id}">`;
                }
            },
            { data: 'cod_trabajador' },
            { data: 'nombres' },
            { data: 'puesto.desc_puesto' },

            {
                data: 'horarios',
                render: function (horarios, type, row, meta) {

                    if (!horarios || horarios.length === 0) {
                        return '<span class="text-gray-400 italic">Sin horario</span>';
                    }

                    let html = '<div class="space-y-1">';

                    horarios.forEach((h, index) => {

                        let containerClass = 'bg-gray-100 text-gray-800';
                        let turnoClass = 'bg-blue-200 text-blue-800';
                        let badge = '';

                        if (h.origen === 'Actual') {
                            badge = `
                <span class="px-2 py-0.5 rounded-full text-xs 
                             bg-gray-300 text-gray-800">
                    Actual
                </span>
            `;
                        }

                        if (h.origen === 'Nuevo') {
                            containerClass = 'bg-green-100 border border-green-300 text-green-900';
                            turnoClass = 'bg-green-200 text-green-800';

                            badge = `
                <span class="px-2 py-0.5 rounded-full text-xs 
                             bg-green-300 text-green-900 font-semibold">
                    Nuevo
                </span>
            `;
                        }

                        html += `
            <div class="flex items-center gap-2 
                        ${containerClass} rounded-lg px-3 py-1 text-sm">
                
                <span class="font-medium">
                    ${h.hora_ini} – ${h.hora_fin}
                </span>

                <span class="px-2 py-0.5 rounded-full text-xs ${turnoClass}">
                    ${h.turno_txt}
                </span>

                ${badge}

                <button 
                    type="button"
                    class="btnEliminarHorario ml-auto text-red-600 hover:text-red-800"
                    data-index="${index}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
                    });

                    html += '</div>';

                    return html;
                }



            }

        ],
        paging: true,
        searching: true,
        info: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
        }
    });
    tabla.rows().every(function () {
        let data = this.data();
        if (!data.horarios) {
            data.horarios = [];
            this.data(data);
        }
    });

    // Evento cambio de sucursal
    $('#sucursal').change(function () {

        let sucursalID = $(this).val();

        // Reset UI
        $('#dias').text('-');
        $('#horas').text('-');
        $('#tolerancia').text('-');
        $('#horario_laboral').text('-');

        // Si no selecciona sucursal limpiamos tabla
        if (!sucursalID) {
            tabla.clear().draw();
            return;
        }

        // ============================
        // 1) Obtener detalles sucursal
        // ============================
        $.get('/api/sucursal/details/' + sucursalID, function (data) {

            let dias = '-';

            if (data.sucursal.dias_laborales) {
                try {
                    if (Array.isArray(data.sucursal.dias_laborales)) {
                        dias = data.sucursal.dias_laborales.join(', ');
                    } else {
                        let arr = JSON.parse(data.sucursal.dias_laborales);
                        dias = Array.isArray(arr) ? arr.join(', ') : '-';
                    }
                } catch (e) {
                    dias = '-';
                }
            }

            $('#dias').text(dias);
            $('#horas').text(data.horarios.horas_laborales ?? '-');
            $('#tolerancia').text(data.horarios.tolerancia + ' Min.' ?? '-');
            $('#horario_laboral').text(data.horarios.hora_ini + ' - ' + data.horarios.hora_fin + ': ' + data.horarios.turno_txt ?? '-');
            $('#horario_laboral_hidd').val(data.horarios.hora_ini + '-' + data.horarios.hora_fin);

        });

        // ============================
        // 2) Obtener empleados
        // ============================
        $.ajax({
            url: '/api/empleados/sucursal/' + sucursalID,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                tabla.clear();
                tabla.rows.add(data);
                tabla.draw();
            },
            error: function (xhr, status, error) {

                console.error('Error AJAX');
                console.error('Status:', status);
                console.error('HTTP:', xhr.status);
                console.error('Error:', error);

                // Si Laravel devuelve JSON con mensaje
                if (xhr.responseJSON) {
                    console.error('Response JSON:', xhr.responseJSON);
                } else {
                    console.error('Response Text:', xhr.responseText);
                }

                alert('Error cargando empleados (ver consola)');
            }
        });

    });

    $(document).on('change', '#selectAll', function () {
        $('.chkEmpleado').prop('checked', $(this).is(':checked'));
    });
    $('#formHorario').on('submit', function (e) {
        e.preventDefault(); // SIEMPRE prevenir

        // Limpia inputs previos
        $('.dinamico').remove();

        let empleadosProcesados = 0;
        let form = this;

        tabla.rows().every(function () {
            let data = this.data();

            if (
                data.horarios_nuevos.length === 0 &&
                data.horarios_eliminados.length === 0
            ) return;

            empleadosProcesados++;

            let empleadoID = data.id;

            // Nuevos
            data.horarios_nuevos.forEach(h => {
                $('<input>', {
                    type: 'hidden',
                    name: 'nuevos[' + empleadoID + '][]',
                    value: h.id,
                    class: 'dinamico'
                }).appendTo(form);
            });

            // Eliminados
            data.horarios_eliminados.forEach(idHorario => {
                $('<input>', {
                    type: 'hidden',
                    name: 'eliminados[' + empleadoID + '][]',
                    value: idHorario,
                    class: 'dinamico'
                }).appendTo(form);
            });
        });

        if (empleadosProcesados === 0) {
            alertify.error('No hay cambios para guardar');
            return;
        }

        // CONFIRMACIÓN
        alertify.confirm(
            'Confirmar cambios',
            '¿Deseas guardar los cambios de horarios?',
            function () {
                form.submit();
            },
            function () {
                alertify.message('Operación cancelada');
            }
        );
    });

    $('#btnAgregarHorario').click(function () {

        let select = $('#horario option:selected');

        let horarioID = select.val();
        if (!horarioID) {
            alertify.error('Selecciona un horario');
            return;
        }

        let horario = {
            id: horarioID,
            hora_ini: select.data('hora_ini'),
            hora_fin: select.data('hora_fin'),
            turno_txt: select.data('turno'),
            origen: 'Nuevo'
        };

        // Obtener empleados seleccionados
        let rows = tabla.rows().nodes();
        let algunoSeleccionado = false;

        $(rows).each(function () {
            let checkbox = $(this).find('.chkEmpleado');

            if (checkbox.is(':checked')) {
                algunoSeleccionado = true;

                let row = tabla.row(this);
                let data = row.data();

                // Evitar duplicados

                if (!horarioDentroDeSucursal(horario.hora_ini, horario.hora_fin)) {
                    alertify.error('El horario ' + horario.hora_ini + ' - ' + horario.hora_fin + ' está fuera del rango laboral de la sucursal.');
                    return;
                }
                // Validar solapamiento
                let hayCruce = data.horarios.some(h =>
                    horariosSeSobreponen(horario.hora_ini,horario.hora_fin,h.hora_ini, h.hora_fin)
                );

                if (hayCruce) {
                    alertify.error(
                        `El horario ${horario.hora_ini} - ${horario.hora_fin} se sobrepone con uno ya asignado al trabajador: ` + data.cod_trabajador
                    );
                    return;
                }

                let existe = data.horarios.some(h => h.id == horario.id);
                if (!existe) {
                    alertify.success('Horario agregado al trabajador: ' + data.cod_trabajador);
                    data.horarios.push(horario);
                    data.horarios_nuevos.push(horario);
                    row.data(data).invalidate();
                } else {
                    alertify.error('Horario ya asignado al trabajador: ' + data.cod_trabajador);
                }
            }
        });

        if (!algunoSeleccionado) {
            alertify.error('Se debe seleccionar al menos a un empleado');
        }
    });
    function horariosSeSobreponen(ini1, fin1, ini2, fin2) {
        return ini1 < fin2 && fin1 > ini2;
    }
    function horarioDentroDeSucursal(horaIni, horaFin) {

        let rangoSucursal = document.getElementById('horario_laboral_hidd').value; // "07:00:00-19:00:00"

        if (!rangoSucursal) return false;

        let [sucIni, sucFin] = rangoSucursal.split('-');

        // Convertir HH:mm:ss a minutos
        const toMinutes = (h) => {
            let [hh, mm, ss] = h.split(':').map(Number);
            return hh * 60 + mm + (ss / 60);
        };

        let sucIniMin = toMinutes(sucIni);
        let sucFinMin = toMinutes(sucFin);
        let horIniMin = toMinutes(horaIni);
        let horFinMin = toMinutes(horaFin);

        return horIniMin >= sucIniMin && horFinMin <= sucFinMin;
    }

    $('#tablaTrabajadores tbody').on('click', '.btnEliminarHorario', function () {

        let btn = $(this);
        let rowEl = btn.closest('tr');
        let row = tabla.row(rowEl);
        let data = row.data();

        let index = btn.data('index');
        let horario = data.horarios[index];

        if (!horario) return;

        // Confirmación
        alertify.confirm(
            'Eliminar horario',
            `¿Deseas eliminar el horario ${horario.hora_ini} - ${horario.hora_fin}?`,
            function () {

                let horario = data.horarios[index];

                // Si viene de BD → marcar para eliminar
                if (horario.origen === 'Actual') {
                    data.horarios_eliminados.push(horario.id);
                }

                // Si es nuevo → quitarlo de nuevos
                if (horario.origen === 'Nuevo') {
                    data.horarios_nuevos = data.horarios_nuevos.filter(h => h.id != horario.id);
                }
                // Eliminar del array
                data.horarios.splice(index, 1);

                // Redibujar solo la fila
                row.data(data).invalidate();

                alertify.success('Horario eliminado');
            },
            function () { ///Cancelar
            }
        );
    });

});
