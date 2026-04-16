<x-app-layout title="Asignar horarios">
    <x-slot name="header">
        <x-encabezado :crearEdit="'Asignar horarios a trabajadores'" />
    </x-slot>

    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. MENSAJES DE ALERTA --}}
            @if (session('success') || session('error'))
                <div class="rounded-lg shadow-sm p-4 mb-4 border-l-4 {{ session('success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700' }}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="{{ session('success') ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' }} fa-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('success') ?? session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form id="formHorario" action="{{ route('horario_trabajador.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 2. COLUMNA IZQUIERDA: CONFIGURACIÓN (Sucursal, Horario y Remoto) --}}
                    <div class="lg:col-span-1 space-y-6">
                        
                        {{-- TARJETA DE CONFIGURACIÓN CON TABS --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden" x-data="{ tabActiva: 'horarios' }">
                            
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-sliders-h mr-2 text-blue-500"></i> Asignaciones
                                </h3>
                            </div>

                            {{-- TABS NAV --}}
                            <div class="flex border-b border-gray-200 bg-white">
                                <button type="button" @click="tabActiva = 'horarios'"
                                        :class="tabActiva === 'horarios' ? 'text-blue-600 border-blue-600 bg-blue-50/50' : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                                        class="w-1/2 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex justify-center items-center gap-2">
                                    <i class="fa-regular fa-clock"></i> Horarios
                                </button>

                                <button type="button" @click="tabActiva = 'remoto'"
                                        :class="tabActiva === 'remoto' ? 'text-purple-600 border-purple-600 bg-purple-50/50' : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                                        class="w-1/2 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex justify-center items-center gap-2">
                                    <i class="fa-solid fa-house-laptop"></i> Home Office
                                </button>
                            </div>
                            
                            <div class="p-5 space-y-5">
                                {{-- Selector Sucursal (GLOBAL PARA AMBAS PESTAÑAS) --}}
                                <div>
                                    <label for="sucursal" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-store text-gray-400"></i>
                                        </div>
                                        <select id="sucursal" name="id_sucursal" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors">
                                            <option value="">Seleccione una sucursal...</option>
                                            @foreach ($sucursales as $s)
                                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('id_sucursal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                {{-- TAB 1: FORMULARIO HORARIOS --}}
                                <div x-show="tabActiva === 'horarios'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" class="space-y-5">
                                    <div>
                                        <label for="horario" class="block text-xs font-bold text-gray-500 uppercase mb-1">Horario a Asignar *</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-clock text-gray-400"></i>
                                            </div>
                                            <select id="horario" name="id_horario" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors">
                                                <option value="">Seleccione un horario...</option>
                                                @foreach ($horarios as $h)
                                                    <option value="{{ $h->id }}" 
                                                            data-hora_ini="{{ $h->hora_ini }}" 
                                                            data-hora_fin="{{ $h->hora_fin }}" 
                                                            data-dias="{{ implode(', ', $h->dias) }}">
                                                        {{ $h->hora_ini }} - {{ $h->hora_fin }} ({{ implode(', ', $h->dias) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <button type="button" id="btnAgregarHorario" 
                                        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                                        <i class="fas fa-plus-circle mr-2"></i> Asignar Nuevo Horario
                                    </button>
                                </div>

                                {{-- TAB 2: FORMULARIO HOME OFFICE --}}
                                <div x-show="tabActiva === 'remoto'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Días de Trabajo Remoto</label>

                                    <div class="flex justify-between items-center mb-6">
                                        @php
                                            $dias = ['L' => 'Lunes', 'M' => 'Martes', 'X' => 'Miércoles', 'J' => 'Jueves', 'V' => 'Viernes', 'S' => 'Sábado', 'D' => 'Domingo'];
                                        @endphp
                                        
                                        @foreach($dias as $letra => $nombreCompleto)
                                            <label class="cursor-pointer group" title="{{ $nombreCompleto }}">
                                                <input type="checkbox" name="dias_remoto_temp[]" value="{{ $nombreCompleto }}" class="peer sr-only dias-remoto-chk">
                                                <div class="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-200 text-gray-400 font-bold text-xs transition-all peer-checked:bg-purple-600 peer-checked:border-purple-600 peer-checked:text-white hover:border-purple-300 peer-checked:shadow-md">
                                                    {{ $letra }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    <div class="flex gap-2 mt-2">
                                        <button type="button" id="btnAsignarRemoto" class="flex-grow bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg shadow-md transition-all active:scale-95 flex items-center justify-center gap-2 text-sm">
                                            <i class="fa-solid fa-house-circle-check"></i> Asignar remoto
                                        </button>
                                        <button type="button" id="btnEliminarRemoto" class="w-12 bg-white border border-red-200 text-red-500 hover:bg-red-50 font-bold py-2 rounded-lg shadow-sm transition-all active:scale-95 flex items-center justify-center tooltip" title="Quitar Home Office a seleccionados">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TARJETA DE DETALLES (Info visual) --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i> Info. Sucursal
                                </h3>
                            </div>
                            <div class="p-4 grid grid-cols-2 gap-4">
                                <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                    <i class="fas fa-calendar-alt text-blue-400 text-lg mb-1 block"></i>
                                    <span class="text-xs text-gray-500 block uppercase">Días laborales</span>
                                    <span id="dias" class="text-gray-800 font-bold text-sm block">-</span>
                                </div>
                                <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                    <i class="fas fa-business-time text-blue-400 text-lg mb-1 block"></i>
                                    <span class="text-xs text-gray-500 block uppercase">Horario</span>
                                    <span id="horario_laboral" class="text-gray-800 font-bold text-sm block">-</span>
                                    <input type="hidden" id="horario_laboral_hidd">
                                </div>
                                <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                    <i class="fas fa-hourglass-half text-blue-400 text-lg mb-1 block"></i>
                                    <span class="text-xs text-gray-500 block uppercase">Horas</span>
                                    <span id="horas" class="text-gray-800 font-bold text-sm block">-</span>
                                </div>
                                <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                    <i class="fas fa-stopwatch text-blue-400 text-lg mb-1 block"></i>
                                    <span class="text-xs text-gray-500 block uppercase">Tolerancia</span>
                                    <span id="tolerancia" class="text-gray-800 font-bold text-sm block">-</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- 3. COLUMNA DERECHA: LISTA DE TRABAJADORES --}}
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 h-full flex flex-col">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-base font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-users text-gray-400 mr-2"></i> Selección de Personal
                                </h3>
                                
                                <div class="flex gap-2">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full transition-all" id="contadorSeleccionados">
                                        0 seleccionados
                                    </span>

                                    <span class="bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold px-2.5 py-0.5 rounded-full hidden transition-all" id="contadorCambios">
                                        <i class="fas fa-pen mr-1"></i> <span id="numCambios">0</span> cambios sin guardar
                                    </span>
                                </div>
                            </div>

                            <div class="p-0 flex-grow">
                                <div class="overflow-hidden">
                                    <table id="tablaTrabajadores" class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left w-10">
                                                    <div class="flex items-center">
                                                        <input id="selectAll" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                                                    </div>
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Puesto</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Horario Actual</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Footer de Acción --}}
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                                <x-primary-button class="ml-3 px-6 py-3 text-sm shadow-lg transform hover:-translate-y-0.5 transition-all">
                                    <i class="fas fa-check-circle mr-2"></i> Confirmar Asignación
                                </x-primary-button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <style>
            .dataTables_wrapper .dataTables_length select { padding-right: 2rem; border-radius: 0.5rem; border-color: #d1d5db; }
            .dataTables_wrapper .dataTables_filter input { border-radius: 0.5rem; border-color: #d1d5db; padding: 0.5rem 1rem; }
            table.dataTable.no-footer { border-bottom: 1px solid #e5e7eb; }
        </style>
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script src="{{ asset('js/horarios.js') }}"></script>
    @endpush
</x-app-layout>