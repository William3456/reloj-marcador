<x-app-layout title="Puestos">
    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Puestos') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
             @if (session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-300 text-green-800 shadow-sm">
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @elseif (session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-300 text-red-800 shadow-sm">
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
            <div class="bg-gray-100 shadow rounded-lg p-6">

                {{-- Botón agregar --}}
                <div class="flex justify-end mb-4">
                    <a href="{{ route('puestos.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        + Añadir nuevo
                    </a>
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto">
                    <table id="tablapuestos" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Código</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Descripción</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Sucursal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach ($puestos as $d)
                                <tr>
                                    <td class="px-4 py-2">{{ $d->id }}</td>
                                    <td class="px-4 py-2">{{ $d->cod_puesto }}</td>
                                    <td class="px-4 py-2">{{ $d->desc_puesto }}</td>
                                    <td class="px-4 py-2">{{ $d->sucursal->nombre }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="px-2 py-1 text-xs rounded 
                                                    {{ $d->estado == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $d->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('puestos.edit', $d->id) }}"
                                                class="text-blue-600 hover:underline" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ route('puestos.delete', $d->id) }}" method="POST"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este horario?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline" title="Inactivar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
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
            new DataTable('#tablapuestos', {
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