<x-app-layout title="Asignar Horarios a Sucursal">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asignación de Horarios por Sucursal') }}
        </h2>
    </x-slot>

    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Mensajes de feedback --}}
            @if (session('success') || session('error'))
                <div
                    class="rounded-lg shadow-sm p-4 mb-4 border-l-4 {{ session('success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700' }}">
                    <p class="font-bold"><i class="fas {{ session('success') ? 'fa-check' : 'fa-exclamation' }}-circle"></i>
                        {{ session('success') ?? session('error') }}</p>
                </div>
            @endif

            <form id="formAsignacionSucursal" action="{{ route('horario_sucursal.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 2. COLUMNA IZQUIERDA: CONTROLES --}}
                    <div class="lg:col-span-1 space-y-6">

                        {{-- CARD SELECCIÓN --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-sliders-h mr-2 text-blue-500"></i> Configuración
                                </h3>
                            </div>

                            <div class="p-5 space-y-5">
                                {{-- Select Sucursal --}}
                                <div>
                                    <label for="sucursal"
                                        class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal *</label>
                                    <div class="relative">
                                        <select id="sucursal" name="id_sucursal"
                                            class="pl-2 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">Seleccione una sucursal...</option>
                                            @foreach ($sucursales as $s)
                                                <option value="{{ $s->id }}" data-dias='@json($s->dias_laborales)'>
                                                    {{ $s->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Select Horario --}}
                                <div>
                                    <label for="horario"
                                        class="block text-xs font-bold text-gray-500 uppercase mb-1">Horario Disponible
                                        *</label>
                                    <div class="relative">
                                        <select id="horario"
                                            class="pl-2 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">Seleccione un horario...</option>
                                            @foreach ($horarios as $h)
                                                <option value="{{ $h->id }}" data-inicio="{{ $h->hora_ini }}"
                                                    data-fin="{{ $h->hora_fin }}" data-turno="{{ $h->turno_txt }}"
                                                    data-dias='@json($h->dias)'>
                                                    {{ $h->hora_ini }} - {{ $h->hora_fin }}
                                                    ({{ implode(', ', $h->dias ?? []) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p id="error-validacion" class="text-red-500 text-xs mt-2 hidden"></p>
                                </div>

                                <button type="button" id="btnAgregar" disabled
                                    class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all">
                                    <i class="fas fa-plus-circle mr-2"></i> Agregar a la Lista
                                </button>
                            </div>
                        </div>

                        {{-- CARD INFO SUCURSAL --}}
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i> Días Laborales Sucursal
                                </h3>
                            </div>
                            <div class="p-4">
                                <div id="info-dias-sucursal" class="flex flex-wrap gap-2 text-sm text-gray-600">
                                    <span class="italic text-gray-400">Seleccione una sucursal para ver sus
                                        días...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. COLUMNA DERECHA: TABLA DE HORARIOS --}}
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 h-full flex flex-col">
                            <div
                                class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-base font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-clock text-gray-400 mr-2"></i> Horarios a Asignar
                                </h3>

                                <div class="flex gap-2">
                                    {{-- Contador de Cambios (NUEVO) --}}
                                    <span id="badge-cambios"
                                        class="hidden bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold px-2.5 py-0.5 rounded-full animate-pulse">
                                        <i class="fas fa-pen mr-1"></i> 0 cambios
                                    </span>

                                    {{-- Contador Total --}}
                                    <span id="contador-horarios"
                                        class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-200">
                                        0 horarios
                                    </span>
                                </div>
                            </div>

                            <div class="p-0 flex-grow overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Turno</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Entrada / Salida</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                                                Días Aplicables</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">
                                                Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body" class="bg-white divide-y divide-gray-200">
                                        <tr id="row-empty">
                                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">
                                                Seleccione una sucursal y agregue horarios para comenzar.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Footer de Acción --}}
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                                <button type="submit" id="btnGuardar" disabled
                                    class="px-6 py-3 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-lg transform hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-save mr-2"></i> Guardar Asignación
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/horarios_sucursales.js') }}"></script>
    @endpush
</x-app-layout>