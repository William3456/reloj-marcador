<x-app-layout title="Reporte de Asistencia">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reporte de Asistencia (Histórico)
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">

                {{-- SECCIÓN 1: FILTROS (Igual que antes) --}}
                <form method="GET" class="mb-6 border-b border-gray-100 pb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        {{-- Rango Fechas --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Desde</label>
                            <input type="date" name="desde" value="{{ request('desde') ?? date('Y-m-01') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasta</label>
                            <input type="date" name="hasta" value="{{ request('hasta') ?? date('Y-m-d') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>

                        {{-- Sucursal --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sucursal</label>
                            <select name="sucursal"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todas</option>
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>
                                        {{ $suc->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Empleado --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empleado</label>
                            <select name="empleado"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Todos</option>
                                @foreach($empleadosList as $emp)
                                    <option value="{{ $emp->id }}" {{ request('empleado') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->nombres }} {{ $emp->apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div class="w-1/3">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Estado</label>
                            <select name="incidencia" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">Todos (Presentes y Ausentes)</option>
                                <option value="asistencia_ok" {{ request('incidencia') == 'asistencia_ok' ? 'selected' : '' }}>Puntuales</option>
                                <option value="tarde" {{ request('incidencia') == 'tarde' ? 'selected' : '' }}>Tardanzas
                                </option>
                                <option value="ausente" {{ request('incidencia') == 'ausente' ? 'selected' : '' }}>
                                    Ausencias</option>
                                <option value="sin_cierre" {{ request('incidencia') == 'sin_cierre' ? 'selected' : '' }}>
                                    Sin Salida</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm flex items-center">
                                <i class="fa-solid fa-filter mr-2"></i> Filtrar
                            </button>
                            <button type="button" onclick="openPdfModal()"
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow text-sm flex items-center">
                                <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                            </button>
                        </div>
                    </div>
                </form>

                {{-- SECCIÓN 2: RESULTADOS AGRUPADOS --}}
                <div class="space-y-8">

                    @if(isset($marcaciones) && $marcaciones->isNotEmpty())
                        @php
                            // Agrupamos la colección plana por ID de empleado
                            $empleadosGroup = $marcaciones->groupBy(function ($item) {
                                return $item['empleado']->id;
                            });
                        @endphp

                        @foreach($empleadosGroup as $empId => $turnos)
                            @php $empleado = $turnos->first()['empleado']; @endphp

                            {{-- TARJETA DE EMPLEADO --}}
                            <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">

                                {{-- Cabecera Empleado --}}
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg">{{ $empleado->nombres }}
                                            {{ $empleado->apellidos }}</h3>
                                        <div class="text-xs text-gray-500 flex items-center gap-3">
                                            <span><i class="fa-solid fa-id-card mr-1"></i>
                                                {{ $empleado->cod_trabajador }}</span>
                                            <span><i class="fa-solid fa-store mr-1"></i>
                                                {{ $empleado->sucursal->nombre ?? 'Sin Sucursal' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right text-xs">
                                        <span class="block font-bold text-gray-600">Resumen:</span>
                                        <span
                                            class="text-red-600 font-bold mr-2">{{ $turnos->where('estado_key', 'ausente')->count() }}
                                            Ausencias</span>
                                        <span
                                            class="text-orange-600 font-bold">{{ $turnos->where('estado_key', 'tarde')->count() }}
                                            Tardanzas</span>
                                    </div>
                                </div>

                                {{-- Tabla de Turnos del Empleado --}}
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Fecha
                                                </th>
                                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">
                                                    Turno Asignado</th>
                                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">
                                                    Entrada</th>
                                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">
                                                    Salida</th>
                                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">
                                                    Incidencia</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($turnos as $turno)
                                                @php
    $bgRow = 'hover:bg-gray-50';
    $estadoHtml = '<span class="text-green-600 font-bold text-xs">OK</span>';

    if ($turno['estado_key'] == 'ausente') {
        $bgRow = 'bg-red-50 hover:bg-red-100';
        $estadoHtml = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">AUSENTE</span>';
    } elseif ($turno['estado_key'] == 'tarde') {
        $bgRow = 'bg-orange-50 hover:bg-orange-100';

        // --- Lógica de Conversión a Horas CORREGIDA ---
        // 1. Usamos abs() para quitar el negativo (-93 -> 93)
        $minTotal = abs(round($turno['minutos_tarde'])); 
        
        // 2. Calculamos horas y minutos restantes
        $horas = floor($minTotal / 60);
        $minutos = $minTotal % 60;

        // 3. Formateamos el texto
        $textoTiempo = $horas > 0
            ? "+{$horas}h {$minutos}m"  // Ej: +1h 33m
            : "+{$minTotal} min";       // Ej: +45 min

        $estadoHtml = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">TARDE (' . $textoTiempo . ')</span>';
        
    } elseif ($turno['estado_key'] == 'sin_cierre') {
        $bgRow = 'bg-yellow-50 hover:bg-yellow-100';
        $estadoHtml = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">SIN SALIDA</span>';
    }
@endphp
                                                <tr class="{{ $bgRow }}">
                                                    {{-- Fecha --}}
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $turno['fecha']->format('d/m') }}</div>
                                                        <div class="text-xs text-gray-500 capitalize">
                                                            {{ $turno['fecha']->locale('es')->isoFormat('dddd') }}</div>
                                                    </td>

                                                    {{-- Turno --}}
                                                    <td class="px-4 py-2 text-center">
                                                        <span
                                                            class="px-2 py-1 bg-gray-100 rounded text-xs font-mono text-gray-600 border border-gray-200">
                                                            {{ $turno['horario_programado'] }}
                                                        </span>
                                                    </td>

                                                    {{-- Entrada --}}
                                                    <td class="px-4 py-2 text-center text-sm">
                                                        @if($turno['entrada_real'])
                                                            <span
                                                                class="font-bold text-gray-700">{{ $turno['entrada_real']->format('H:i') }}</span>
                                                        @else
                                                            <span class="text-gray-300">--:--</span>
                                                        @endif
                                                    </td>

                                                    {{-- Salida --}}
                                                    <td class="px-4 py-2 text-center text-sm">
                                                        @if($turno['salida_real'])
                                                            <span
                                                                class="font-bold text-gray-700">{{ $turno['salida_real']->format('H:i') }}</span>
                                                        @else
                                                            <span class="text-gray-300">--:--</span>
                                                        @endif
                                                    </td>

                                                    {{-- Estado --}}
                                                    <td class="px-4 py-2 text-center">
                                                        {!! $estadoHtml !!}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach

                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay datos para mostrar</h3>
                            <p class="mt-1 text-sm text-gray-500">Seleccione un rango de fechas y presione Filtrar.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMACIÓN (Reutilizar el mismo código del modal anterior) --}}
    <div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        {{-- ... (Tu código del modal existente) ... --}}
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePdfModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-file-pdf text-red-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Generar Reporte PDF
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Se generará el reporte con los filtros actuales.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btnConfirmarPdf"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Generar</button>
                    <button type="button" onclick="closePdfModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPdfModal() { document.getElementById('pdfModal').classList.remove('hidden'); }
            function closePdfModal() { document.getElementById('pdfModal').classList.add('hidden'); }

            document.getElementById('btnConfirmarPdf').addEventListener('click', function () {
                const params = window.location.search;
                const baseUrl = "{{ route('marcaciones.pdf') }}";
                window.open(baseUrl + params, '_blank');
                closePdfModal();
            });
        </script>
    @endpush
</x-app-layout>