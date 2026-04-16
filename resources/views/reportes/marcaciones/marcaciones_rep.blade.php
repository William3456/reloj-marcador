<x-app-layout title="Reporte de Asistencia">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight tracking-tight">
                Reporte de Asistencia
            </h2>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-chart-line"></i> Auditoría Histórica
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- PANEL DE FILTROS AVANZADOS --}}
            <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
                <div class="bg-gray-50/50 border-b border-gray-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-blue-500"></i> Filtros del Reporte
                    </h3>
                </div>
                <div class="p-6">
                    <form method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            
                            {{-- Fechas --}}
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Desde</label>
                                <input type="date" name="desde" value="{{ request('desde') ?? date('Y-m-01') }}" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Hasta</label>
                                <input type="date" name="hasta" value="{{ request('hasta') ?? date('Y-m-d') }}" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors">
                            </div>

                            {{-- Empleado y Sucursal --}}
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Sucursal</label>
                                <select name="sucursal" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todas las sucursales</option>
                                    @foreach($sucursales as $suc)
                                        <option value="{{ $suc->id }}" {{ request('sucursal') == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Empleado</label>
                                <select name="empleado" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todos los empleados</option>
                                    @foreach($empleadosList as $emp)
                                        <option value="{{ $emp->id }}" {{ request('empleado') == $emp->id ? 'selected' : '' }}>{{ $emp->nombres }} {{ $emp->apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- FILTRO AVANZADO DE INCIDENCIAS --}}
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Filtrar por Estado / Incidencia</label>
                                <select name="incidencia" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-50 cursor-pointer hover:bg-white transition-colors">
                                    <option value="">📋 Mostrar Todos los Registros</option>
                                    <optgroup label="✅ Asistencias">
                                        <option value="presente" {{ request('incidencia') == 'presente' ? 'selected' : '' }}>Asistencia Perfecta (Puntual)</option>
                                        <option value="extra" {{ request('incidencia') == 'extra' ? 'selected' : '' }}>Turnos Extras</option>
                                    </optgroup>
                                    <optgroup label="⚠️ Impuntualidad y Faltas">
                                        <option value="tarde_total" {{ request('incidencia') == 'tarde_total' ? 'selected' : '' }}>Llegadas Tarde (Todas)</option>
                                        <option value="tarde_sin_permiso" {{ request('incidencia') == 'tarde_sin_permiso' ? 'selected' : '' }}>Llegadas Tarde Injustificadas</option>
                                        <option value="ausente" {{ request('incidencia') == 'ausente' ? 'selected' : '' }}>Ausencias Injustificadas</option>
                                    </optgroup>
                                    <optgroup label="📝 Observaciones y Permisos">
                                        <option value="con_permiso" {{ request('incidencia') == 'con_permiso' ? 'selected' : '' }}>Justificados (Con Permisos Aplicados)</option>
                                        <option value="sin_cierre" {{ request('incidencia') == 'sin_cierre' ? 'selected' : '' }}>Olvidos de Salida / Cierres Atrasados</option>
                                    </optgroup>
                                </select>
                            </div>

                            {{-- Botones --}}
                            <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-1">
                                <button type="submit" class="flex-1 bg-gray-900 hover:bg-gray-800 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm text-sm flex items-center justify-center transition-all active:scale-95">
                                    <i class="fa-solid fa-magnifying-glass mr-2"></i> Buscar
                                </button>
                                <button type="button" onclick="openPdfModal()" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm text-sm flex items-center justify-center transition-all active:scale-95">
                                    <i class="fa-solid fa-file-pdf mr-2"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- SECCIÓN 2: RESULTADOS AGRUPADOS --}}
            <div class="space-y-8">
                @if(isset($marcaciones) && $marcaciones->isNotEmpty())
                    @php
                        $empleadosGroup = $marcaciones->groupBy(function ($item) { return $item['empleado']->id; });
                    @endphp

                    @foreach($empleadosGroup as $empId => $turnos)
                        @php $empleado = $turnos->first()['empleado']; @endphp

                        {{-- TARJETA DE EMPLEADO PRO --}}
                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                            
                            {{-- Cabecera Empleado con Mini-Stats --}}
                            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-5 border-b border-gray-200">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                    
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xl shadow-inner">
                                            {{ substr($empleado->nombres, 0, 1) }}{{ substr($empleado->apellidos, 0, 1) }}
                                        </div>
                                        <div>
                                            <h3 class="font-black text-gray-900 text-lg leading-tight">{{ $empleado->nombres }} {{ $empleado->apellidos }}</h3>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                                <span class="flex items-center gap-1"><i class="fa-solid fa-id-badge text-gray-400"></i> {{ $empleado->cod_trabajador }}</span>
                                                <span class="text-gray-300">|</span>
                                                <span class="flex items-center gap-1"><i class="fa-solid fa-store text-gray-400"></i> {{ $empleado->sucursal->nombre ?? 'Sin Sucursal' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Mini Stats --}}
                                    <div class="flex flex-wrap gap-2">
                                        <div class="bg-white border border-gray-100 px-3 py-1.5 rounded-lg shadow-sm text-center">
                                            <p class="text-[10px] uppercase font-bold text-gray-400">Turnos</p>
                                            <p class="text-sm font-black text-gray-700">{{ $turnos->count() }}</p>
                                        </div>
                                        <div class="bg-red-50 border border-red-100 px-3 py-1.5 rounded-lg shadow-sm text-center">
                                            <p class="text-[10px] uppercase font-bold text-red-400">Faltas</p>
                                            <p class="text-sm font-black text-red-600">{{ $turnos->where('estado_key', 'ausente')->count() }}</p>
                                        </div>
                                        <div class="bg-orange-50 border border-orange-100 px-3 py-1.5 rounded-lg shadow-sm text-center">
                                            <p class="text-[10px] uppercase font-bold text-orange-400">Tardanzas</p>
                                            <p class="text-sm font-black text-orange-600">{{ $turnos->whereIn('estado_key', ['tarde', 'tarde_con_permiso'])->count() }}</p>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-lg shadow-sm text-center">
                                            <p class="text-[10px] uppercase font-bold text-blue-400">Permisos</p>
                                            <p class="text-sm font-black text-blue-600">{{ $turnos->filter(function($t){ return !empty($t['permiso_info']); })->count() }}</p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- Tabla Limpia --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Fecha</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Turno Asignado</th>
                                            {{-- NUEVA COLUMNA TOLERANCIA --}}
                                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Tolerancia</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Marcaciones Reales</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Estado</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-1/4">Observaciones / Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($turnos as $turno)
                                            @php
                                                $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700"><i class="fa-solid fa-check mr-1.5"></i> ASISTENCIA</span>';
                                                
                                                switch ($turno['estado_key']) {
                                                    case 'ausente':
                                                        $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700"><i class="fa-solid fa-xmark mr-1.5"></i> AUSENTE</span>';
                                                        break;
                                                    case 'tarde':
                                                        $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-orange-100 text-orange-700"><i class="fa-solid fa-clock-rotate-left mr-1.5"></i> RETARDO</span>';
                                                        break;
                                                    case 'tarde_con_permiso':
                                                        $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-orange-50 text-orange-600 border border-orange-200"><i class="fa-solid fa-file-shield mr-1.5"></i> RETARDO (C/PERMISO)</span>';
                                                        break;
                                                    case 'permiso':
                                                        $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200"><i class="fa-solid fa-file-contract mr-1.5"></i> PERMISO APLICADO</span>';
                                                        break;
                                                    case 'sin_cierre':
                                                        if ($turno['salida_real']) {
                                                            $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-600 border border-red-200"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> CIERRE ATRASADO</span>';
                                                        } else {
                                                            $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fa-solid fa-person-walking-arrow-right mr-1.5"></i> SIN SALIDA</span>';
                                                        }
                                                        break;
                                                    case 'extra':
                                                        $estadoHtml = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700"><i class="fa-solid fa-plus mr-1.5"></i> TURNO EXTRA</span>';
                                                        break;
                                                }
                                            @endphp

                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-bold text-gray-800">{{ $turno['fecha']->format('d/m/Y') }}</div>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-[11px] text-gray-400 font-medium uppercase">{{ $turno['fecha']->locale('es')->isoFormat('dddd') }}</span>
                                                        
                                                        
                                                        @if($turno['es_dia_remoto'])
                                                            <span class="text-[8px] bg-purple-100 text-purple-700 font-black px-1.5 py-0.5 rounded border border-purple-200 uppercase flex items-center gap-1">
                                                                <i class="fa-solid fa-house-laptop"></i> Remoto
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <span class="px-3 py-1 bg-gray-100 rounded-md text-xs font-mono font-medium text-gray-600 border border-gray-200">{{ $turno['horario_programado'] }}</span>
                                                </td>
                                                
                                                {{-- NUEVA CELDA TOLERANCIA --}}
                                                <td class="px-6 py-4 text-center">
                                                    <span class="text-xs font-bold text-gray-500">{{ $turno['tolerancia'] > 0 ? $turno['tolerancia'] . ' min' : '-' }}</span>
                                                </td>

                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[9px] text-gray-400 font-bold uppercase">Entrada</span>
                                                            @if($turno['entrada_real'])
                                                                <span class="text-sm font-bold text-gray-700">{{ $turno['entrada_real']->format('H:i') }}</span>
                                                            @else
                                                                <span class="text-sm font-bold text-gray-300">--:--</span>
                                                            @endif
                                                        </div>
                                                        <span class="text-gray-300">/</span>
                                                        <div class="flex flex-col items-center">
                                                            <span class="text-[9px] text-gray-400 font-bold uppercase">Salida</span>
                                                            @if($turno['salida_real'])
                                                                <span class="text-sm font-bold text-gray-700">
                                                                    {{ $turno['salida_real']->format('H:i') }}
                                                                    @if($turno['es_olvido_salida'])
                                                                        <span class="text-red-500 ml-1" title="Marcación fuera de tiempo"><i class="fa-solid fa-circle-exclamation text-[10px]"></i></span>
                                                                    @endif
                                                                </span>
                                                            @else
                                                                <span class="text-sm font-bold text-gray-300">--:--</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    {!! $estadoHtml !!}
                                                </td>
                                                <td class="px-6 py-4 text-left">
                                                    <div class="space-y-2">
                                                        @if($turno['minutos_tarde'] > 0)
                                                            @php
                                                                $minTotal = round($turno['minutos_tarde']);
                                                                $horas = floor($minTotal / 60);
                                                                $minutos = $minTotal % 60;
                                                                $textoTiempo = $horas > 0 ? "{$horas}h {$minutos}m" : "{$minTotal} min";
                                                            @endphp
                                                            <div class="flex items-start gap-2">
                                                                <i class="fa-solid fa-clock text-orange-500 text-xs mt-0.5"></i>
                                                                <div>
                                                                    <p class="text-[10px] font-bold text-orange-700 leading-none">Tiempo de retraso</p>
                                                                    <p class="text-xs font-black text-gray-800">{{ $textoTiempo }}</p>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if(!empty($turno['permiso_info']))
                                                            <div class="flex items-start gap-2 bg-blue-50/50 p-2 rounded-lg border border-blue-100">
                                                                <i class="fa-solid fa-file-contract text-blue-500 text-xs mt-0.5"></i>
                                                                <div>
                                                                    <p class="text-[10px] font-bold text-blue-700 leading-none">{{ $turno['permiso_info']['tipo'] }}</p>
                                                                    @if($turno['permiso_info']['motivo'])
                                                                        <p class="text-[10px] text-gray-600 italic mt-1 leading-tight line-clamp-2">"{{ $turno['permiso_info']['motivo'] }}"</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(!$turno['minutos_tarde'] && empty($turno['permiso_info']))
                                                            <span class="text-xs text-gray-400 italic">Sin observaciones</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="mx-auto h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-folder-open text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-800">No hay datos para mostrar</h3>
                        <p class="mt-1 text-sm text-gray-500">Ajusta los filtros arriba y presiona "Buscar" para generar el reporte.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMACIÓN --}}
    <div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closePdfModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-50 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-file-pdf text-red-500 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-black text-gray-900" id="modal-title">Exportar a PDF</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Se generará un documento formal con los registros y filtros que tienes seleccionados actualmente en pantalla.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" id="btnConfirmarPdf" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-bold text-white hover:bg-red-700 sm:w-auto sm:text-sm transition-colors">Generar Documento</button>
                    <button type="button" onclick="closePdfModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm transition-colors">Cancelar</button>
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