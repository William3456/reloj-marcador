@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modal.css') }}">
@endpush
<x-app-layout title="Empleados">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Empleados') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 rounded-md bg-green-100 border border-green-300 text-green-800">
                    {{ session('success') }}
                </div>
            @elseif (session('error'))
                <div class="mb-4 p-3 rounded-md bg-red-100 border border-red-300 text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            <div class="bg-gray-100 shadow rounded-lg p-6">

                {{-- Botón agregar --}}
                <div class="flex justify-end mb-4">
                    <a href="{{ route('empleados.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        + Añadir nuevo
                    </a>
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto">

                    <table id="tablaEmpleados" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-12">
                                    ID</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Código</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Nombre completo</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Puesto</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Depto.</th>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Sucursal</th>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Login</th>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Ver</th>
                                <th
                                    class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Acción</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @foreach ($empleados as $e)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-2 py-2 text-center font-medium text-gray-900">{{ $e->id }}</td>

                                    <td class="px-2 py-2 text-gray-600">{{ $e->cod_trabajador }}</td>

                                    <td class="px-2 py-2">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900">
                                                {{ $e->nombres }} {{ $e->apellidos }}
                                            </span>
                                            @if(auth()->user()?->empleado?->id === $e->id)
                                                <span
                                                    class="mt-0.5 w-fit px-1.5 py-0.5 text-[10px] leading-tight rounded bg-blue-100 text-blue-700 font-semibold border border-blue-200">
                                                    Tú
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-2 py-2 text-gray-600 truncate max-w-[150px]"
                                        title="{{ $e->puesto->desc_puesto ?? '' }}">
                                        {{ $e->puesto->desc_puesto ?? '—' }}
                                    </td>

                                    <td class="px-2 py-2 text-gray-600 truncate max-w-[150px]"
                                        title="{{ $e->departamento->nombre_depto ?? '' }}">
                                        {{ $e->departamento->nombre_depto ?? '—' }}
                                    </td>

                                    <td class="px-2 py-2 text-center text-gray-600">{{ $e->sucursal->nombre ?? '—' }}</td>

                                    <td class="px-2 py-2 text-center">
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-full font-medium border
                                {{ $e->login == 1 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                            {{ $e->login == 1 ? 'Sí' : 'No' }}
                                        </span>
                                    </td>

                                    <td class="px-2 py-2 text-center">
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-full font-medium border
                                {{ $e->estado == 1 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                            {{ $e->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-2 py-2 text-center">
                                        <button type="button"
                                            class="text-gray-500 hover:text-blue-600 transition-colors p-1"
                                            title="Ver detalles" onclick="verEmpleado({{ $e->id }})">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>

                                    <td class="px-2 py-2 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('empleados.edit', $e->id) }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-1"
                                                title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ route('empleados.delete', $e->id) }}" method="POST"
                                                onsubmit="return confirm('¿Seguro que deseas inactivar este empleado?')"
                                                class="inline">
                                                @csrf @method('DELETE')
                                                @if(auth()->user()?->empleado?->id !== $e->id)
                                                    <button class="text-red-500 hover:text-red-700 transition-colors p-1"
                                                        title="Inactivar">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                @else
                                                    <span class="text-gray-300 cursor-not-allowed p-1"
                                                        title="No puedes desactivarte a ti mismo">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </span>
                                                @endif
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="modalEmpleado"
                        class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center pt-20 hidden z-50 transition-opacity duration-300">

                        <div id="modalCaja"
                            class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative transition-all duration-300 opacity-0 -translate-y-5">

                            <!-- Botón cerrar -->
                            <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 transition"
                                onclick="cerrarModalEmpleado()">
                                <i class="fa-solid fa-xmark text-xl"></i>
                            </button>

                            <!-- Encabezado -->
                            <div class="text-center mb-5">
                                <h2 class="text-2xl font-semibold flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-user text-blue-600"></i>
                                    Detalles del empleado
                                </h2>
                                <p class="text-gray-500 text-sm mt-1">Información general del trabajador</p>
                            </div>

                            <div id="contenidoEmpleado" class="space-y-3"></div>

                            <div class="text-center mt-6">
                                <button
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 active:bg-blue-800 transition"
                                    onclick="cerrarModalEmpleado()">
                                    Cerrar
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts') {{-- DataTables --}}
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script>
            new DataTable('#tablaEmpleados', {
                responsive: true,
                paging: true,
                searching: true,
                info: true,
                autoWidth: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                }
            });
            function verEmpleado(id) {

                fetch(`/empleados/${id}/info`)
                    .then(res => res.json())
                    .then(e => {

                        let estadoColor = e.estado == 1 ? "text-green-600" : "text-red-600";
                        let estadoTexto = e.estado == 1 ? "Activo" : "Inactivo";
                        let horariosHTML = '';

                        if (e.horarios && e.horarios.length > 0) {

                            horariosHTML = '<div class="space-y-1">';

                            e.horarios.forEach(h => {
                                horariosHTML += `
                        <div class="flex items-center justify-between
                                    bg-white border border-gray-200
                                    rounded-lg px-3 py-1 text-sm">

                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800">
                                    ${h.hora_ini} – ${h.hora_fin}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs 
                                            bg-blue-200 text-blue-800">
                                    ${h.turno_txt}
                                </span>
                            </div>
                        </div>
                    `;
                            });

                            horariosHTML += '</div>';

                        } else {
                            horariosHTML = `
                    <span class="text-gray-400 italic text-sm">
                        Sin horarios asignados
                    </span>
                `;
                        }

                        let html = `
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                        <h3 class="font-semibold text-gray-700 mb-2 text-sm">Datos Personales</h3>
                                        <p><strong>Código:</strong> ${e.cod_trabajador ?? ''}</p>
                                        <p><strong>Nombres:</strong> ${e.nombres ?? ''}</p>
                                        <p><strong>Apellidos:</strong> ${e.apellidos ?? ''}</p>
                                        <p><strong>Edad:</strong> ${e.edad ?? ''}</p>
                                        <p><strong>DUI:</strong> ${e.documento ?? ''}</p>
                                        <p><strong>Correo:</strong> ${e.correo ?? ''}</p>
                                        <p><strong>Dirección:</strong> ${e.direccion ?? ''}</p>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                        <h3 class="font-semibold text-gray-700 mb-2 text-sm">Información Laboral</h3>
                                        <p><strong>Puesto:</strong> ${e.puesto?.desc_puesto ?? ''}</p>
                                        <p><strong>Departamento:</strong> ${e.departamento?.nombre_depto ?? ''}</p>
                                        <p><strong>Sucursal:</strong> ${e.sucursal?.nombre ?? ''}</p>
                                        <p><strong>Empresa:</strong> ${e.empresa?.nombre ?? ''}</p>
                                        <p><strong>Login:</strong> ${e.login == 1 ? "Sí" : "No"}</p>
                                        <p><strong>Rol:</strong>  ${e.user?.rol?.rol_name ?? "Sin Rol"}</p>
                                        <p><strong>Estado:</strong> 
                                            <span class="${estadoColor} font-semibold">${estadoTexto}</span>
                                        </p>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                        <h3 class="font-semibold text-gray-700 mb-2 text-sm">Horarios asignados</h3>
                                        ${horariosHTML}
                                    </div>
                                `;
                        e.horarios.forEach(h => {
                            console.log(h.hora_ini, h.hora_fin, h.turno_txt);
                        });

                        document.getElementById("contenidoEmpleado").innerHTML = html;

                        const modal = document.getElementById("modalEmpleado");
                        const caja = document.getElementById("modalCaja");

                        // Mostrar modal
                        modal.classList.remove("hidden");

                        // Animación: fade-in al fondo
                        setTimeout(() => modal.classList.add("modal-fade-in"), 10);

                        // Animación: slide-in al cuadro
                        setTimeout(() => caja.classList.add("modal-slide-in"), 10);
                    });
            }


            function cerrarModalEmpleado() {
                const modal = document.getElementById("modalEmpleado");
                const caja = document.getElementById("modalCaja");

                // Animación de salida
                modal.classList.remove("modal-fade-in");
                modal.classList.add("modal-fade-out");

                caja.classList.remove("modal-slide-in");
                caja.classList.add("modal-slide-out");

                // Esperar la animación y ocultar
                setTimeout(() => {
                    modal.classList.add("hidden");
                    modal.classList.remove("modal-fade-out");
                    caja.classList.remove("modal-slide-out");
                }, 300);
            }

        </script>
    @endpush @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css"> @endpush
</x-app-layout>