<x-app-layout title="Sucursales">
    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sucursales') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                @if($role = Auth::user()->id_rol == 1)
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
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dirección
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Correo
                                    Encargado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach ($sucursales as $s)
                                <tr>
                                    <td class="px-4 py-2">{{ $s->id }}</td>
                                    <td class="px-4 py-2">{{ $s->nombre }}</td>
                                    <td class="px-4 py-2">{{ $s->direccion }}</td>
                                    <td class="px-4 py-2">{{ $s->correo_encargado }}</td>
                                    <td class="px-4 py-2">{{ $s->telefono }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="px-2 py-1 text-xs rounded 
                                                    {{ $s->estado == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $s->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('sucursales.edit', $s->id) }}"
                                                class="text-blue-600 hover:underline" title="Editar">
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

    @push('scripts')
        {{-- DataTables --}}
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script>
            new DataTable('#tablaSucursales', {
                responsive: true,
                paging: true,
                searching: true,
                info: true,
                autoWidth: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                }
            });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    @endpush
</x-app-layout>