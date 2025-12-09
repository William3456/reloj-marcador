<x-app-layout title="Horarios">
    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Horarios') }}
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
            <div class="bg-white shadow rounded-lg p-6">

                {{-- Botón agregar --}}
                <div class="flex justify-end mb-4">
                    <a href="{{ route('horarios.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        + Añadir nuevo
                    </a>
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto">
                    <table id="tablaHorarios" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Turno</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Hora inicio</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Hora fin</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Horas laborales</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo horario</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Tolerancia (min)</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Salida requerida</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Estado</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach ($horarios as $h)
                                <tr>
                                    <td class="px-4 py-2">{{ $h->id }}</td>
                                    <td class="px-4 py-2">{{ $h->turno_txt }}</td>
                                    <td class="px-4 py-2">{{ $h->hora_ini }}</td>
                                    <td class="px-4 py-2">{{ $h->hora_fin }}</td>
                                    <td class="px-4 py-2">{{ $h->horas_laborales }}</td>
                                    <td class="px-4 py-2">{{ $h->tipo_horario }}</td>
                                    <td class="px-4 py-2">{{ $h->tolerancia_txt }}</td>
                                    <td class="px-4 py-2">{{ $h->requiere_salida_txt  }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="px-2 py-1 text-xs rounded 
                                                    {{ $h->estado == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $h->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('horarios.edit', $h->id) }}"
                                                class="text-blue-600 hover:underline" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ route('horarios.delete', $h->id) }}" method="POST"
                                                onsubmit="return confirm('¿Seguro que deseas inactivar este horario?')">
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
            new DataTable('#tablaHorarios', {
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