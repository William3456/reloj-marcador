$(document).ready(function () {

    // 1. Variables de Estado Globales
    let rows_selected = []; // Memoria de IDs seleccionados
    let totalCambios = 0;   // Contador de acciones (Asignar/Eliminar)

    // Función para actualizar la UI del contador de cambios
    function actualizarBadgeCambios() {
        let badge = $('#contadorCambios');
        let numSpan = $('#numCambios');
        
        numSpan.text(totalCambios+' ');

        if (totalCambios > 0) {
            badge.removeClass('hidden').addClass('inline-flex animate-pulse'); // Efecto visual
            // Quitar animación después de un momento para no molestar
            setTimeout(() => badge.removeClass('animate-pulse'), 1000);
        } else {
            badge.addClass('hidden').removeClass('inline-flex');
        }
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
            if (!horarios || horarios.length === 0) {
                return '<div class="p-2 text-gray-400 text-xs italic bg-gray-50 rounded text-center">Sin asignación</div>';
            }

            let html = '<div class="flex flex-col gap-2">';

            horarios.forEach((h, index) => {
                let isNew = h.origen === 'Nuevo';
                
                // --- AJUSTE DE COLORES AQUÍ ---
                // Nuevo: Fondo verde suave, borde verde claro, borde izquierdo fuerte verde
                // Actual: Fondo blanco, borde gris, borde izquierdo azul
                let containerClasses = isNew 
                    ? 'bg-green-50 border border-green-200 border-l-4 border-l-green-500' 
                    : 'bg-white border border-gray-200 border-l-4 border-l-blue-300';
                
                let timeColor = isNew ? 'text-green-900' : 'text-gray-800';
                let iconColor = isNew ? 'text-green-600' : 'text-gray-400';

                // Formatear horas
                let timeStr = `${h.hora_ini.substring(0, 5)} - ${h.hora_fin.substring(0, 5)}`;

                html += `
                    <div class="relative group flex items-start justify-between p-2 rounded shadow-sm transition-all hover:shadow-md ${containerClasses}">
                        
                        <div class="flex flex-col">
                            
                            <span class="font-bold text-sm ${timeColor} font-mono">
                                <i class="far fa-clock text-xs mr-1 ${iconColor}"></i>${timeStr}
                            </span>
                            
                            
                            <span class="text-[10px] text-gray-500 leading-tight mt-0.5" title="${h.turno_txt}">
                                ${h.turno_txt}
                            </span>
                        </div>

                        
                        <div class="flex flex-col items-end justify-between h-full pl-2 gap-1">
                            ${isNew 
                                ? '<span class="text-[9px] font-bold uppercase tracking-wider text-green-700 bg-green-100 px-1 rounded">Nuevo</span>' 
                                : '' 
                            }
                            
                            <button type="button" 
                                class="btnEliminarHorario text-gray-400 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-white/50" 
                                data-index="${index}" 
                                title="Eliminar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
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
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        
        // CALLBACK: Mantener checkboxes marcados al paginar
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

    // Asegurar estructura de datos
    tabla.rows().every(function () {
        let data = this.data();
        if (!data.horarios) { data.horarios = []; this.data(data); }
        if (!data.horarios_nuevos) data.horarios_nuevos = [];
        if (!data.horarios_eliminados) data.horarios_eliminados = [];
    });

    // ==========================================
    // LÓGICA DE SELECCIÓN (CHECKBOXES)
    // ==========================================
    
    function actualizarContadorSeleccionados() {
        let count = rows_selected.length;
        let span = $('#contadorSeleccionados');
        span.text(count + ' seleccionados');
        if(count > 0) {
            span.removeClass('bg-blue-100 text-blue-800').addClass('bg-blue-600 text-white shadow-md');
        } else {
            span.removeClass('bg-blue-600 text-white shadow-md').addClass('bg-blue-100 text-blue-800');
        }
    }

    $('#selectAll').on('click', function () {
        let isChecked = this.checked;
        let rows = tabla.rows({ 'search': 'applied' }).data();
        rows.each(function(data) {
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

    // ==========================================
    // CAMBIO DE SUCURSAL (RESET)
    // ==========================================
    $('#sucursal').change(function () {
        let sucursalID = $(this).val();

        // Reset UI completa
        $('#dias, #horas, #tolerancia, #horario_laboral').text('-');
        rows_selected = []; 
        $('#selectAll').prop('checked', false);
        actualizarContadorSeleccionados();
        
        // RESET CAMBIOS
        totalCambios = 0;
        actualizarBadgeCambios();

        if (!sucursalID) {
            tabla.clear().draw();
            return;
        }

        // Cargar Info Sucursal
        $.get('/api/sucursal/details/' + sucursalID, function (data) {
            let dias = '-';
            if (data.sucursal.dias_laborales) {
                try {
                    let raw = data.sucursal.dias_laborales;
                    let arr = Array.isArray(raw) ? raw : JSON.parse(raw);
                    dias = Array.isArray(arr) ? arr.join(', ') : '-';
                } catch (e) {}
            }
            $('#dias').text(dias);
            $('#horas').text(data.horarios.horas_laborales ?? '-');
            $('#tolerancia').text((data.horarios.tolerancia ?? '-') + ' Min.');
            $('#horario_laboral').text((data.horarios.hora_ini ?? '-') + ' - ' + (data.horarios.hora_fin ?? '-') + ': ' + (data.horarios.turno_txt ?? '-'));
            $('#horario_laboral_hidd').val(data.horarios.hora_ini + '-' + data.horarios.hora_fin);
        });

        // Cargar Empleados
        $.ajax({
            url: '/api/empleados/sucursal/' + sucursalID,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                // Inicializar arrays vacíos para cada empleado
                data.forEach(emp => {
                    emp.horarios_nuevos = [];
                    emp.horarios_eliminados = [];
                });
                tabla.clear();
                tabla.rows.add(data);
                tabla.draw();
            },
            error: function () { alertify.error('Error cargando empleados'); }
        });
    });

    // ==========================================
    // BOTÓN AGREGAR HORARIO
    // ==========================================
    $('#btnAgregarHorario').click(function () {
        let select = $('#horario option:selected');
        let horarioID = select.val();

        if (!horarioID) { alertify.error('Selecciona un horario'); return; }

        let horarioObj = {
            id: horarioID,
            hora_ini: select.data('hora_ini'),
            hora_fin: select.data('hora_fin'),
            turno_txt: select.data('turno'),
            origen: 'Nuevo'
        };

        if (rows_selected.length === 0) { alertify.error('Seleccione al menos un empleado'); return; }

        if (!horarioDentroDeSucursal(horarioObj.hora_ini, horarioObj.hora_fin)) {
            alertify.error('Horario fuera del rango de la sucursal');
            return;
        }

        let cambiosEnEstaAccion = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let rowId = data.id.toString();

            if ($.inArray(rowId, rows_selected) !== -1) {
                
                let hayCruce = data.horarios.some(h => horariosSeSobreponen(horarioObj.hora_ini, horarioObj.hora_fin, h.hora_ini, h.hora_fin));
                if (hayCruce) {
                    alertify.notify(`Cruce: ${data.cod_trabajador}`, 'warning', 5);
                    return; 
                }

                let existe = data.horarios.some(h => h.id == horarioObj.id);
                if (!existe) {
                    let nuevoH = JSON.parse(JSON.stringify(horarioObj));
                    
                    data.horarios.push(nuevoH);
                    
                    if(!data.horarios_nuevos) data.horarios_nuevos = [];
                    data.horarios_nuevos.push(nuevoH);

                    // AUMENTAR CONTADOR DE CAMBIOS
                    totalCambios++;
                    cambiosEnEstaAccion++;

                    this.data(data).invalidate();
                } 
            }
        });

        if(cambiosEnEstaAccion > 0){
            alertify.success(`Horario asignado a ${cambiosEnEstaAccion} empleados`);
            tabla.draw(false);
            actualizarBadgeCambios(); // Actualizar UI visual
        }
    });

    // ==========================================
    // BOTÓN ELIMINAR HORARIO (INDIVIDUAL)
    // ==========================================
    $('#tablaTrabajadores tbody').on('click', '.btnEliminarHorario', function () {
        let btn = $(this);
        let rowEl = btn.closest('tr');
        let row = tabla.row(rowEl);
        let data = row.data();
        let index = btn.data('index');
        let horario = data.horarios[index];

        if (!horario) return;

        alertify.confirm('Eliminar', `¿Quitar horario ${horario.hora_ini} - ${horario.hora_fin}?`,
            function () {
                // Inicializar arrays si faltan
                if(!data.horarios_eliminados) data.horarios_eliminados = [];
                if(!data.horarios_nuevos) data.horarios_nuevos = [];

                if (horario.origen === 'Actual') {
                    data.horarios_eliminados.push(horario.id);
                }
                if (horario.origen === 'Nuevo') {
                    data.horarios_nuevos = data.horarios_nuevos.filter(h => h.id != horario.id);
                }

                data.horarios.splice(index, 1);
                
                // AUMENTAR CONTADOR DE CAMBIOS
                totalCambios++;
                actualizarBadgeCambios();

                row.data(data).invalidate().draw(false);
                alertify.success('Eliminado');
            },
            function () {}
        ).set('labels', {ok:'Sí, eliminar', cancel:'Cancelar'}); 
    });

    // ==========================================
    // UTILS
    // ==========================================
    function horariosSeSobreponen(ini1, fin1, ini2, fin2) { return ini1 < fin2 && fin1 > ini2; }

    function horarioDentroDeSucursal(horaIni, horaFin) {
        let rango = $('#horario_laboral_hidd').val(); 
        if (!rango || rango === "undefined-undefined") return true; 
        let [sucIni, sucFin] = rango.split('-');
        const tm = (h) => { let [hh, mm] = h.split(':').map(Number); return hh * 60 + mm; };
        
        // Simplificación: validamos strings ISO (HH:mm:ss)
        return tm(horaIni) >= tm(sucIni) && tm(horaFin) <= tm(sucFin);
    }

    // ==========================================
    // SUBMIT GLOBAL
    // ==========================================
    $('#formHorario').on('submit', function (e) {
        e.preventDefault();
        $('.dinamico').remove();
        let form = this;
        let itemsProcesados = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let empID = data.id;
            
            // Validamos que existan arrays
            let nuevos = data.horarios_nuevos || [];
            let eliminados = data.horarios_eliminados || [];

            if (nuevos.length > 0 || eliminados.length > 0) {
                itemsProcesados++;
                nuevos.forEach(h => {
                    $('<input>', { type: 'hidden', name: `nuevos[${empID}][]`, value: h.id, class: 'dinamico' }).appendTo(form);
                });
                eliminados.forEach(idH => {
                    $('<input>', { type: 'hidden', name: `eliminados[${empID}][]`, value: idH, class: 'dinamico' }).appendTo(form);
                });
            }
        });

        if (itemsProcesados === 0) { alertify.error('No hay cambios pendientes'); return; }

        alertify.confirm('Guardar Cambios', `Se procesarán cambios para ${itemsProcesados} empleados. ¿Continuar?`,
            function () { form.submit(); },
            function () {}
        );
    });
});