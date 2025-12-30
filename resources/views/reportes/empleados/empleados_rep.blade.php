<x-app-layout title="Generar Reporte - Empleados">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Generar Reporte PDF - Empleados
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                {{-- Sección 1: Formulario de Filtros --}}
                <form method="GET" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                        {{-- Sucursal --}}
                        <div>
                            <label for="sucursal" class="block text-sm font-medium text-gray-700">Sucursal</label>
                            <select name="sucursal" id="sucursal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @if (Auth::user()->id_rol == 1)
                                    <option value="">Todas</option>
                                @endif
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>
                                        {{ $suc->nombre }} {{ $suc->estado == 1 ? '(Activa)' : '(Inactiva)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Estado --}}
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select name="estado" id="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        {{-- Login --}}
                        <div>
                            <label for="login" class="block text-sm font-medium text-gray-700">Acceso al Sistema</label>
                            <select name="login" id="login" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="1" {{ request('login') === '1' ? 'selected' : '' }}>Sí (Tiene Login)</option>
                                <option value="0" {{ request('login') === '0' ? 'selected' : '' }}>No (Sin Login)</option>
                            </select>
                        </div>

                        {{-- Rol --}}
                        <div>
                            <label for="rol" class="block text-sm font-medium text-gray-700">Rol</label>
                            <select name="rol" id="rol" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id }}" {{ request('rol') == $rol->id ? 'selected' : '' }}>
                                        {{ $rol->rol_name ?? $rol->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Puesto --}}
                        <div>
                            <label for="puesto" class="block text-sm font-medium text-gray-700">Puesto</label>
                            <select name="puesto" id="puesto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                @foreach($puestos as $puesto)
                                    <option value="{{ $puesto->id }}" {{ request('puesto') == $puesto->id ? 'selected' : '' }}>
                                        {{ $puesto->desc_puesto }} {{ $puesto->estado == 1 ? '(Activo)' : '(Inactivo)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Departamento --}}
                        <div>
                            <label for="departamento" class="block text-sm font-medium text-gray-700">Departamento</label>
                            <select name="departamento" id="departamento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                @foreach($departamentos as $depto)
                                    <option value="{{ $depto->id }}" {{ request('departamento') == $depto->id ? 'selected' : '' }}>
                                        {{ $depto->nombre_depto }} {{ $depto->estado == 1 ? '(Activo)' : '(Inactivo)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="inline-block text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-12 py-2.5 text-center shadow-md">
                            Filtrar Resultados
                        </button>
                    </div>
                </form>

                <hr class="my-8 border-gray-200">

                {{-- Sección 2: Tabla de Resultados --}}
                <div class="overflow-x-auto">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Resultados ({{ isset($empleados) ? count($empleados) : 0 }}) (Orden alfabético)
                    </h3>
                    
                    <table id="tablaFiltros" class="min-w-full divide-y divide-gray-200 display nowrap" style="width:100%">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cód.</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sucursal</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acceso</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Depto.</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-center">
                            @forelse($empleados ?? [] as $empleado)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $empleado->cod_trabajador ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">{{ $empleado->nombres }} {{ $empleado->apellidos }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $empleado->sucursal->nombre ?? 'N/A' }}</td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($empleado->login == 1)
                                            <span class="text-blue-600 font-bold">Sí</span>
                                        @else
                                            <span class="text-gray-400">No</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($empleado->login == 1 && $empleado->user && $empleado->user->rol)
                                            {{ $empleado->user->rol->rol_name ?? $empleado->user->rol->name }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $empleado->puesto->desc_puesto ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $empleado->departamento->nombre_depto ?? 'N/A' }}</td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($empleado->estado == 1)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                {{-- No ponemos tr vacío aquí porque DataTables lo maneja mejor automáticamente --}}
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Botón PDF --}}
                <div class="mt-8 flex justify-end">
                    <button type="button" onclick="openPdfModal()" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

{{-- MODAL DE CONFIRMACIÓN --}}
<div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        {{-- Capa oscura de fondo --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePdfModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        {{-- Icono Exclamación/PDF --}}
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirmar Generación
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Estás a punto de generar un PDF con los filtros actuales. Se abrirá en una nueva pestaña para que puedas visualizarlo y guardarlo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="btnConfirmarPdf" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Sí, Generar PDF
                </button>
                <button type="button" onclick="closePdfModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Mostrar modal
    function openPdfModal() {
        // Usamos jQuery o Vanilla JS para quitar la clase hidden
        document.getElementById('pdfModal').classList.remove('hidden');
    }

    // Ocultar modal
    function closePdfModal() {
        document.getElementById('pdfModal').classList.add('hidden');
    }

    // Al confirmar
    document.getElementById('btnConfirmarPdf').addEventListener('click', function() {
        // 1. Tomamos los filtros de la URL actual (ej: ?sucursal=1&estado=activo)
        const params = window.location.search;
        
        // 2. Definimos la ruta base del PDF (usando helper de Laravel)
        const baseUrl = "{{ route('empleados.pdf') }}";
        
        // 3. Abrimos la nueva pestaña con la ruta + filtros
        window.open(baseUrl + params, '_blank');
        
        // 4. Cerramos el modal
        closePdfModal();
    });
</script>
@endpush

    @push('scripts')
        {{-- DataTables --}}
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script>
            new DataTable('#tablaFiltros', {
                responsive: true,
                paging: true,
                searching: false,
                info: true,
                autoWidth: true,
                ordering: false,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                },
                
            });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    @endpush
</x-app-layout>