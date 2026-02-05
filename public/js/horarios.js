$(document).ready(function () {

    // 1. Variables de Estado Globales
    let rows_selected = []; // Memoria de IDs seleccionados
    let totalCambios = 0;   // Contador de acciones (Asignar/Eliminar)

    // Función para actualizar la UI del contador de cambios
    function actualizarBadgeCambios() {
        let badge = $('#contadorCambios');
        let numSpan = $('#numCambios');

        numSpan.text(totalCambios + ' ');

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
                        let listaDias = h.dias;

                        // 1. Si viene como texto, intentamos parsearlo
                        if (typeof listaDias === 'string') {
                            try {
                                // Intento A: ¿Es un JSON Array? Ej: '["Lunes", "Martes"]'
                                listaDias = JSON.parse(listaDias);
                            } catch (error) {
                                // Intento B: Si falla JSON, asumimos que es texto separado por comas
                                // Ej: "jueves, viernes, sábado" -> ["jueves", "viernes", "sábado"]
                                if (listaDias.includes(',')) {
                                    listaDias = listaDias.split(',').map(d => d.trim());
                                } else {
                                    // Si no tiene comas, es un solo día en texto plano
                                    listaDias = [listaDias];
                                }
                            }
                        }

                        // 2. Si es nulo o undefined, aseguramos que sea un array vacío
                        if (!Array.isArray(listaDias)) {
                            listaDias = [];
                        }

                        // 3. Ahora sí es seguro usar .map() USANDO LA VARIABLE PROCESADA (listaDias)
                        const diasTexto = listaDias
                            .map(dia => dia.charAt(0).toUpperCase() + dia.slice(1).toLowerCase().substring(0, 2))
                            .join(', ');

                        console.log(diasTexto); // Resultado esperado: "Jue, Vie, Sáb"
                        html += `
                    <div class="relative group flex items-start justify-between p-2 rounded shadow-sm transition-all hover:shadow-md ${containerClasses}">
                        
                        <div class="flex flex-col">
                            
                            <span class="font-bold text-sm ${timeColor} font-mono">
                                <i class="far fa-clock text-xs mr-1 ${iconColor}"></i>${timeStr}
                            </span>
                            
                            
                            <span class="text-[10px] text-gray-500 leading-tight mt-0.5" title="${h.diasTexto}">
                                ${diasTexto}
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

    // ==========================================
    // CAMBIO DE SUCURSAL (RESET)
    // ==========================================
    function cargarDatosSucursal() {
        let sucursalID = $('#sucursal').val();

        // Reset UI completa
        $('#dias, #horas, #tolerancia, #horario_laboral').text('-');
        rows_selected = [];
        $('#selectAll').prop('checked', false);
        if (typeof actualizarContadorSeleccionados === 'function') actualizarContadorSeleccionados();

        // RESET CAMBIOS
        totalCambios = 0;
        if (typeof actualizarBadgeCambios === 'function') actualizarBadgeCambios();

        if (!sucursalID) {
            tabla.clear().draw();
            return;
        }

        // Cargar Info Sucursal
        $.get('/api/sucursal/details/' + sucursalID, function (data) {
            // ---------------------------------------------------------
            // PARTE 1: DÍAS LABORALES DE LA SUCURSAL (General)
            // ---------------------------------------------------------
            // Inicializamos con un guión por si no hay datos
            let htmlDiasSucursal = '<span class="text-gray-400 italic text-xs">-</span>';

            if (data.sucursal && data.sucursal.dias_laborales) {
                try {
                    let raw = data.sucursal.dias_laborales;
                    // Validamos si es Array o String JSON
                    let arr = Array.isArray(raw) ? raw : JSON.parse(raw);

                    if (Array.isArray(arr) && arr.length > 0) {
                        // Generamos las "tarjetitas" azules
                        let badges = arr.map(dia => {
                            let d = dia.charAt(0).toUpperCase() + dia.slice(1).toLowerCase();
                            return `<span class="inline-block bg-blue-100 text-blue-800 text-[10px] font-medium px-2 py-0.5 rounded-full mr-1 mb-1 shadow-sm border border-blue-200">${d}</span>`;
                        }).join('');

                        // Contenedor flex para que se ajusten automáticamente
                        htmlDiasSucursal = `<div class="flex flex-wrap content-start w-full">${badges}</div>`;
                    }
                } catch (e) {
                    console.error("Error procesando días sucursal:", e);
                }
            }

            // ---------------------------------------------------------
            // PARTE 2: HORARIOS, TOLERANCIA Y HORAS (Específico)
            // ---------------------------------------------------------
            let htmlHoras = [];
            let htmlTolerancia = [];
            let htmlRangos = [];
            let listaHidden = [];

            if (data.horarios && Array.isArray(data.horarios)) {
                data.horarios.forEach(function (item, index) {
                    let h = item.horario;

                    if (h) {
                        // Definimos si lleva línea separadora (todos menos el último)
                        let separatorClass = (index < data.horarios.length - 1)
                            ? 'border-b border-gray-100 pb-2 mb-2'
                            : '';

                        // ETIQUETA "HORARIO X" (Para identificar las columnas de la derecha)
                        let labelHtml = `<span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Horario ${index + 1}</span>`;

                        // --- Procesar Texto Corto de Días (Lun, Mar...) ---
                        let diasTextoCorto = '';
                        if (h.dias) {
                            try {
                                let r = Array.isArray(h.dias) ? h.dias : JSON.parse(h.dias);
                                if (Array.isArray(r)) {
                                    // slice(1,3) generará abreviaciones de 3 letras (Ej: Lunes -> Lun)
                                    diasTextoCorto = r.map(d =>
                                        d.charAt(0).toUpperCase() + d.slice(1, 3).toLowerCase()
                                    ).join(', ');
                                }
                            } catch (e) {
                                console.error("Error en días del Horario " + (index + 1), e);
                            }
                        }

                        // --- B. Columna HORAS ---
                        htmlHoras.push(`
                    <div class="${separatorClass} flex flex-col justify-center h-full">
                        ${labelHtml}
                        <span class="text-gray-700 font-medium">${h.horas_laborales ?? '-'}</span>
                    </div>
                `);

                        // --- C. Columna TOLERANCIA ---
                        htmlTolerancia.push(`
                    <div class="${separatorClass} flex flex-col justify-center h-full">
                        ${labelHtml}
                        <span class="text-gray-600">${(h.tolerancia ?? '0') + ' Min.'}</span>
                    </div>
                `);

                        // --- D. Columna HORARIO (Rango + Días abreviados) ---
                        // Aquí es clave mostrar 'diasTextoCorto' para saber qué días aplica este horario
                        htmlRangos.push(`
                    <div class="${separatorClass} flex flex-col justify-center">
                         ${labelHtml}
                        <span class="font-bold text-gray-800 text-sm">${h.hora_ini ?? '-'} - ${h.hora_fin ?? '-'}</span>
                        ${diasTextoCorto ? `<span class="text-[10px] text-gray-500 leading-tight mt-0.5">${diasTextoCorto}</span>` : ''}
                    </div>
                `);

                        // Acumulamos para el input hidden
                        listaHidden.push({
                            inicio: h.hora_ini ?? '00:00',
                            fin: h.hora_fin ?? '00:00',
                            dias: h.dias
                        });
                    }
                });
            }

            // ---------------------------------------------------------
            // 3. RENDERIZADO FINAL
            // ---------------------------------------------------------

            // Columna 1: Badges de Días (Sucursal)
            $('#dias').html(htmlDiasSucursal);

            // Columnas 2, 3, 4: Info detallada de Horarios
            $('#horas').html(htmlHoras.length > 0 ? htmlHoras.join('') : '-');
            $('#tolerancia').html(htmlTolerancia.length > 0 ? htmlTolerancia.join('') : '-');
            $('#horario_laboral').html(htmlRangos.length > 0 ? htmlRangos.join('') : '-');

            // Input hidden separado por pipes
            $('#horario_laboral_hidd').val(JSON.stringify(listaHidden));
        });

        // Cargar Empleados
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
            error: function () { alertify.error('Error cargando empleados'); }
        });
    }
    // Si el select ya tiene un valor (por ejemplo, al recargar), carga los datos
    if ($('#sucursal').val()) {
        cargarDatosSucursal();
    }

    // Escuchar cambios futuros
    $('#sucursal').on('change', cargarDatosSucursal);

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
            dias: select.data('dias'),
            origen: 'Nuevo'
        };

        if (rows_selected.length === 0) { alertify.error('Seleccione al menos un empleado'); return; }

        if (!horarioDentroDeSucursal(horarioObj.hora_ini, horarioObj.hora_fin, horarioObj.dias)) {
            alertify.error('El horario o el día no está permitido en esta sucursal');
            return;
        }

        let cambiosEnEstaAccion = 0;

        tabla.rows().every(function () {
            let data = this.data();
            let rowId = data.id.toString();

            if ($.inArray(rowId, rows_selected) !== -1) {

                // Dentro de tu evento $('#btnAgregarHorario').click...

                let hayCruce = data.horarios.some(h =>
                    horariosSeSobreponen(
                        horarioObj.hora_ini,
                        horarioObj.hora_fin,
                        horarioObj.dias,      // <--- Nuevo parámetro (Días nuevos)
                        h.hora_ini,
                        h.hora_fin,
                        h.dias                // <--- Nuevo parámetro (Días existentes)
                    )
                );

                if (hayCruce) {
                    alertify.notify(`Cruce de horario detectado para: ${data.cod_trabajador}`, 'warning', 5);
                    return;
                }

                let existe = data.horarios.some(h => h.id == horarioObj.id);
                if (!existe) {
                    let nuevoH = JSON.parse(JSON.stringify(horarioObj));

                    data.horarios.push(nuevoH);

                    if (!data.horarios_nuevos) data.horarios_nuevos = [];
                    data.horarios_nuevos.push(nuevoH);

                    // AUMENTAR CONTADOR DE CAMBIOS
                    totalCambios++;
                    cambiosEnEstaAccion++;

                    this.data(data).invalidate();
                }
            }
        });

        if (cambiosEnEstaAccion > 0) {
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
                if (!data.horarios_eliminados) data.horarios_eliminados = [];
                if (!data.horarios_nuevos) data.horarios_nuevos = [];

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
            function () { }
        ).set('labels', { ok: 'Sí, eliminar', cancel: 'Cancelar' });
    });

    // ==========================================
    // UTILS
    // ==========================================
    function horariosSeSobreponen(ini1, fin1, dias1, ini2, fin2, dias2) {
        // 1. Normalizar horas a "HH:MM"
        const clean = (t) => t ? t.substring(0, 5) : '';

        // 2. Verificar solapamiento de horas (Lógica original)
        // (A empieza antes de que B termine) Y (A termina después de que B empiece)
        const horasChocan = clean(ini1) < clean(fin2) && clean(fin1) > clean(ini2);

        if (!horasChocan) return false; // Si las horas no chocan, no importa el día.

        // 3. Verificar coincidencia de días
        // Normalizamos ambos arrays de días para asegurarnos que sean Arrays reales
        const d1 = normalizarDias(dias1);
        const d2 = normalizarDias(dias2);

        // Revisamos si hay algún día en común
        const diasCoinciden = d1.some(dia => d2.includes(dia));

        return diasCoinciden;
    }

    // Función auxiliar para asegurarnos de tener siempre un Array de strings limpios
    function normalizarDias(diasInput) {
        if (!diasInput) return [];

        let lista = diasInput;

        // Si viene como string JSON o CSV, lo convertimos
        if (typeof lista === 'string') {
            try {
                lista = JSON.parse(lista);
            } catch (e) {
                lista = lista.split(',');
            }
        }

        if (!Array.isArray(lista)) lista = [lista];

        // Normalizamos texto (trim y minúsculas opcional si tu data es inconsistente)
        return lista.map(d => String(d).trim());
    }

    // ==========================================
    // VALIDACIÓN DE RANGO
    // ==========================================

    function horarioDentroDeSucursal(horaIni, horaFin, diasSolicitados) {
        let rangoStr = $('#horario_laboral_hidd').val();

        // Validaciones básicas de existencia
        if (!rangoStr || rangoStr === "undefined-undefined" || rangoStr.trim() === '' || rangoStr === '-' || rangoStr === '[]') {
            return false;
        }

        let reglasSucursal = [];
        try {
            reglasSucursal = JSON.parse(rangoStr);
        } catch (e) {
            console.error("Error leyendo reglas de sucursal", e);
            return false;
        }

        // --- CORRECCIÓN AQUÍ: Manejo híbrido de JSON o Texto Plano ---
        let diasCheck = [];

        if (Array.isArray(diasSolicitados)) {
            // Caso 1: Ya es un array (perfecto)
            diasCheck = diasSolicitados;
        } else if (typeof diasSolicitados === 'string') {
            try {
                // Caso 2: Intentamos ver si es JSON (ej: '["Lunes"]')
                diasCheck = JSON.parse(diasSolicitados);
            } catch (e) {
                // Caso 3: Si falla JSON, es texto plano separado por comas (ej: "jueves, viernes")
                // Esto arreglará el error de "Unexpected token d in domingo"
                diasCheck = diasSolicitados.split(',').map(d => d.trim());
            }
        }

        // Aseguramos que siempre sea array, aunque venga vacío
        if (!Array.isArray(diasCheck)) diasCheck = [diasCheck];
        // -------------------------------------------------------------

        // Función auxiliar tiempo
        const tm = (h) => {
            if (!h) return 0;
            let [hh, mm] = h.split(':').map(Number);
            return hh * 60 + mm;
        };

        const tIni = tm(horaIni);
        const tFin = tm(horaFin);

        // Iteramos CADA día que queremos asignar
        let todosLosDiasCubiertos = diasCheck.every(diaRequerido => {
            let reglaValida = reglasSucursal.some(regla => {
                // Aquí también aplicamos la misma lógica de seguridad por si acaso
                let diasRegla = [];
                if (Array.isArray(regla.dias)) {
                    diasRegla = regla.dias;
                } else {
                    try {
                        diasRegla = JSON.parse(regla.dias);
                    } catch (e) {
                        diasRegla = regla.dias.split(',').map(d => d.trim());
                    }
                }

                if (!diasRegla.includes(diaRequerido)) {
                    return false;
                }
                return tIni >= tm(regla.inicio) && tFin <= tm(regla.fin);
            });
            return reglaValida;
        });

        return todosLosDiasCubiertos;
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
            function () { }
        );
    });
});