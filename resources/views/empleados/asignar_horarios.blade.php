<x-app-layout title="Asignar horarios">
    <x-slot name="header">
        <x-encabezado :crearEdit="'Asignar horarios a trabajadores'" />
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4"> {{-- Mantenemos el ancho 7xl --}}
            {{-- Contenedor principal del formulario --}}
            <div class="bg-gray-100 shadow-xl rounded-xl p-4"> {{-- Padding general reducido a p-4 --}}

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

                {{-- FORMULARIO PRINCIPAL --}}
                <form id="formHorario" action="{{ route('horario_trabajador.store') }}" method="POST">
                    @csrf

                    {{-- Panel de Selección de Sucursal y Detalles (Compacto) --}}
                    {{-- Panel de Selección de Sucursal y Detalles (Ahora incluye el Horario a asignar) --}}
                    <div class="bg-white shadow-lg rounded-xl p-5 border border-gray-100 mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i> Asignación y Sucursal
                        </h2>

                        {{-- Contenedor de las dos selecciones principales: Sucursal y Horario --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">

                            {{-- Selección de Sucursal --}}
                            <div>
                                <label for="sucursal" class="block text-xs font-medium text-gray-700 mb-1">
                                    Sucursal *
                                </label>
                                <select id="sucursal" name="id_sucursal"
                                    class="block w-full border-gray-300 bg-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition text-sm py-1.5 px-3">
                                    <option value="">Seleccione...</option>
                                    @foreach ($sucursales as $s)
                                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_sucursal')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Seleccion de Horario para trabajadores (MOVIDO AL LADO DE SUCURSAL) --}}
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <label for="horario" class="block text-xs font-medium text-gray-700 mb-1">
                                        Horario para trabajadores *
                                    </label>
                                    <select id="horario" name="id_horario" class="block w-full border-gray-300 bg-white rounded-lg shadow-sm 
                   focus:ring-blue-500 focus:border-blue-500 transition 
                   text-sm py-1.5 px-3">
                                        <option value="">Seleccione...</option>
                                        @foreach ($horarios as $h)
                                            <option value="{{ $h->id }}" data-hora_ini="{{ $h->hora_ini }}"
                                                data-hora_fin="{{ $h->hora_fin }}" data-turno="{{ $h->turno_txt }}">
                                                {{ $h->hora_ini }} - {{ $h->hora_fin }}: {{ $h->turno_txt }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="button" id="btnAgregarHorario" class="h-[34px] px-4 rounded-lg text-sm font-medium
                                                                                    bg-emerald-600 text-white shadow
                                                                                    hover:bg-emerald-700 transition">
                                    + Agregar horario
                                </button>
                            </div>


                        </div>
                        {{-- Fin del contenedor de las dos selecciones --}}

                        {{-- Detalles de la Sucursal (Cards Compactas) --}}
                        <h3 class="text-lg font-semibold text-gray-800 mt-5 mb-3">
                            Detalles de la sucursal
                        </h3>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            {{-- Días Laborales --}}
                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200 shadow-sm">
                                <p class="text-xs text-blue-600 font-medium mb-0.5">Días laborales</p>
                                <p id="dias" class="text-gray-900 font-bold text-base text-center">-</p>
                            </div>
                            {{-- Horario Laboral --}}
                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200 shadow-sm">
                                <p class="text-xs text-blue-600 font-medium mb-0.5">Horario laboral</p>
                                <p id="horario_laboral" class="text-gray-900 font-bold text-base text-center">-</p>
                                <input type="hidden" id="horario_laboral_hidd">
                            </div>

                            {{-- Horas Requeridas --}}
                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200 shadow-sm">
                                <p class="text-xs text-blue-600 font-medium mb-0.5">Horas laborales
                                </p>
                                <p id="horas" class="text-gray-900 font-bold text-base text-center">-</p>
                            </div>

                            {{-- Tolerancia --}}
                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200 shadow-sm">
                                <p class="text-xs text-blue-600 font-medium mb-0.5">Tolerancia</p>
                                <p id="tolerancia" class="text-gray-900 text-center font-bold text-base">-</p>
                            </div>



                        </div>
                    </div>

                    {{-- Panel de Trabajadores y Asignación de Horario (Compacto) --}}
                    <div class="bg-white shadow-lg rounded-xl p-5 border border-gray-100 mb-6"> {{-- Padding reducido a
                        p-5 --}}
                        <h2 class="text-xl font-bold text-gray-800 mb-3 border-b pb-2"> {{-- Título y margen reducidos
                            --}}
                            <i class="fas fa-users-cog text-blue-500 mr-2"></i> Asignación de Horario
                        </h2>

                        {{-- TABLA DE TRABAJADORES (Compacta) --}}
                        <h3 class="text-lg font-semibold text-gray-800 mt-5 mb-3">
                            Trabajadores de la sucursal
                        </h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                            <table id="tablaTrabajadores" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                            {{-- Padding reducido a px-3 py-2 --}}
                                            Código</th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                            Nombre</th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                            Puesto</th>
                                        <th
                                            class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                            Horario (Actuales o por asignar)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-sm"> {{-- Filas de la tabla con
                                    texto pequeño --}}
                                    {{-- Filas de trabajadores cargadas dinámicamente --}}
                                </tbody>
                            </table>
                        </div>

                    </div>

                    {{-- Botón de Guardar (Compacto) --}}
                    <div class="flex justify-center mt-6">
                        <x-primary-button form="formHorario"
                            class="inline-flex items-center px-8 py-2 border border-transparent text-base font-medium rounded-lg shadow-md
                               text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800
                               focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                            <i class="fas fa-save mr-2"></i> Guardar Asignación
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    @push('scripts') {{-- DataTables --}}
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script src="{{ asset('js/horarios.js') }}"></script>
    @endpush
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    @endpush
</x-app-layout>