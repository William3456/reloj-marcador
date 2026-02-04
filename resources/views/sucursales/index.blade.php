<x-app-layout title="Sucursales">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sucursales') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-100 shadow rounded-lg p-6">

                {{-- Botón agregar --}}
                @if(Auth::user()->id_rol == 1)
                <div class="flex justify-end mb-4">
                    <a href="{{ route('sucursales.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        + Añadir nueva
                    </a>
                </div>
                @endif

                {{-- Tabla --}}
                <div class="overflow-x-auto">
                    <table id="tablaSucursales" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach ($sucursales as $s)
                                {{-- CAMBIO 1 y 2: Onclick en la fila completa y estilos de cursor --}}
                                <tr onclick="verSucursal({{ $s->id }})" 
                                    class="cursor-pointer hover:bg-blue-50 transition-colors duration-200 group">
                                    
                                    <td class="px-4 py-2">{{ $s->id }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800 group-hover:text-blue-700 transition-colors">{{ $s->nombre }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ Str::limit($s->direccion, 30) }}</td>
                                    <td class="px-4 py-2">{{ $s->telefono }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs rounded {{ $s->estado == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $s->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    
                                    {{-- CAMBIO 3: Stop Propagation para que Editar/Eliminar no abran el modal --}}
                                    <td class="px-4 py-2 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                        <div class="inline-flex items-center gap-3">
                                            {{-- Botón "Ver" ELIMINADO --}}

                                            <a href="{{ route('sucursales.edit', $s->id) }}"
                                                class="text-blue-600 hover:text-blue-800 p-1" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <button type="button"
                                                @click="$dispatch('open-confirm-modal', { 
                                                    url: '{{ route('sucursales.delete', $s->id) }}',
                                                    title: 'Inactivar sucursal?',
                                                    message: 'La sucursal será inactivada',
                                                    buttonText: 'Inactivar'
                                                })"
                                                class="text-red-500 hover:text-red-700 p-1" title="Inactivar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE SUCURSAL (Igual que antes) --}}
    <div id="modalSucursal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center pt-20 hidden z-50 transition-opacity duration-300">
        <div id="modalCajaSucursal" class="bg-white w-full max-w-lg rounded-xl shadow-xl p-6 relative transition-all duration-300 opacity-0 -translate-y-5">
            <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 transition" onclick="cerrarModalSucursal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <div class="text-center mb-5">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-2">
                    <i class="fa-solid fa-store text-blue-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-semibold text-gray-800">Detalles de Sucursal</h2>
                <p class="text-gray-500 text-sm">Información general y operativa</p>
            </div>

            <div id="contenidoSucursal" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
            </div>

            <div class="text-center mt-6 pt-4 border-t border-gray-100">
                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg shadow hover:bg-gray-300 transition" onclick="cerrarModalSucursal()">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        
        <script>
            function verSucursal(id) {
                fetch(`/sucursales/${id}/info`)
                    .then(res => res.json())
                    .then(s => {
                        let estadoColor = s.estado == 1 ? "text-green-600" : "text-red-600";
                        let estadoTexto = s.estado == 1 ? "Activa" : "Inactiva";

                        // 1. Renderizar Horarios
                        let horariosHTML = '';
                        if (s.horarios && s.horarios.length > 0) {
                            horariosHTML = '<div class="grid grid-cols-1 gap-2">';
                            s.horarios.forEach(h => {
                                let diasHorario = [];
                                try { diasHorario = typeof h.dias === 'string' ? JSON.parse(h.dias) : h.dias; } catch(e) { diasHorario = []; }
                                let diasBadges = diasHorario.map(d => `<span class="text-[10px] px-1.5 py-0.5 bg-gray-200 rounded text-gray-600">${d.substring(0,3)}</span>`).join(' ');

                                horariosHTML += `
                                    <div class="flex flex-col bg-white border border-gray-200 rounded-lg p-2 text-sm shadow-sm">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-gray-800"><i class="far fa-clock text-blue-500 mr-1"></i> ${h.hora_ini} - ${h.hora_fin}</span>
                                            <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-100">Turno: ${h.turno_txt}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            ${diasBadges}
                                        </div>
                                    </div>
                                `;
                            });
                            horariosHTML += '</div>';
                        } else {
                            horariosHTML = `<div class="p-3 bg-gray-50 rounded border border-gray-200 text-center text-gray-400 text-sm italic">Sin horarios asignados</div>`;
                        }

                        // 2. Renderizar Días Laborales
                        let diasSucursalHTML = '';
                        let diasArray = [];
                        try { diasArray = typeof s.dias_laborales === 'string' ? JSON.parse(s.dias_laborales) : s.dias_laborales; } catch (e) { diasArray = []; }

                        if(Array.isArray(diasArray) && diasArray.length > 0) {
                             diasSucursalHTML = diasArray.map(dia => `<span class="px-2 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded text-xs font-semibold">${dia}</span>`).join(' ');
                        } else {
                            diasSucursalHTML = '<span class="text-gray-400 text-xs">No especificado</span>';
                        }
                        
                        let mapsLink = '#';
                        if(s.latitud && s.longitud) {
                            mapsLink = `https://www.google.com/maps/search/?api=1&query=${s.latitud},${s.longitud}`;
                        }

                        let html = `
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-blue-500 rotate-45 transform translate-x-8 -translate-y-8"></div>
                                <h3 class="font-bold text-lg text-gray-800 mb-1">${s.nombre}</h3>
                                <p class="text-sm text-gray-500 mb-3"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> ${s.direccion}</p>
                                <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                                    <div><p class="text-xs text-gray-400 uppercase font-bold">Estado</p><p class="${estadoColor} font-bold">${estadoTexto}</p></div>
                                    <div><p class="text-xs text-gray-400 uppercase font-bold">Teléfono</p><p class="text-gray-700">${s.telefono ?? 'N/A'}</p></div>
                                    <div class="col-span-2"><p class="text-xs text-gray-400 uppercase font-bold">Encargado / Correo</p><p class="text-gray-700 truncate">${s.correo_encargado ?? 'N/A'}</p></div>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-200 flex justify-between items-center">
                                    <div><p class="text-xs text-gray-400 uppercase font-bold mb-1">Días Laborales</p><div class="flex flex-wrap gap-1">${diasSucursalHTML}</div></div>
                                    ${s.latitud ? `<a href="${mapsLink}" target="_blank" class="flex flex-col items-center text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-map-marked-alt text-2xl mb-1"></i><span>Ver Mapa</span></a>` : ''}
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-blue-50 p-2 rounded border border-blue-100 text-center"><p class="text-xl font-bold text-blue-600">${s.cant_empleados ?? 0}</p><p class="text-[10px] uppercase text-blue-400 font-bold">Empleados</p></div>
                                <div class="bg-orange-50 p-2 rounded border border-orange-100 text-center"><p class="text-xl font-bold text-orange-600">${s.rango_marcacion_mts ?? 0}m</p><p class="text-[10px] uppercase text-orange-400 font-bold">Rango marcación</p></div>
                                <div class="bg-purple-50 p-2 rounded border border-purple-100 text-center"><p class="text-xl font-bold text-purple-600">${s.margen_error_gps_mts ?? 0}m</p><p class="text-[10px] uppercase text-purple-400 font-bold">Margen error GPS</p></div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <h3 class="font-semibold text-gray-700 mb-3 text-sm flex items-center"><i class="fas fa-calendar-alt mr-2 text-gray-400"></i> Horarios Asignados</h3>
                                ${horariosHTML}
                            </div>
                        `;
                        document.getElementById("contenidoSucursal").innerHTML = html;
                        abrirModalSucursal();
                    })
                    .catch(err => { console.error(err); alert("Error al cargar datos de la sucursal"); });
            }

            function abrirModalSucursal() {
                const modal = document.getElementById("modalSucursal");
                const caja = document.getElementById("modalCajaSucursal");
                modal.classList.remove("hidden");
                setTimeout(() => modal.classList.add("opacity-100"), 10);
                setTimeout(() => { caja.classList.remove("opacity-0", "-translate-y-5"); caja.classList.add("opacity-100", "translate-y-0"); }, 10);
            }

            function cerrarModalSucursal() {
                const modal = document.getElementById("modalSucursal");
                const caja = document.getElementById("modalCajaSucursal");
                modal.classList.remove("opacity-100");
                caja.classList.remove("opacity-100", "translate-y-0");
                caja.classList.add("opacity-0", "-translate-y-5");
                setTimeout(() => { modal.classList.add("hidden"); }, 300);
            }

            new DataTable('#tablaSucursales', {
                responsive: true, paging: true, searching: true,
                language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' }
            });

            // 1. Evento Clic fuera del modal
            document.getElementById('modalSucursal').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalSucursal();
                }
            });

            // 2. Evento Tecla Escape
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('modalSucursal');
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    cerrarModalSucursal();
                }
            });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    @endpush
</x-app-layout>