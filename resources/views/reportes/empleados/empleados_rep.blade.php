<x-app-layout title="Reporte de empleados">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight tracking-tight">
                Reporte de empleados
            </h2>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-users"></i> Directorio y accesos
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Panel de filtros avanzados --}}
            <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
                <div class="bg-gray-50/50 border-b border-gray-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-blue-500"></i> Filtros del reporte
                    </h3>
                </div>
                <div class="p-6">
                    <form method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                            {{-- Sucursal --}}
                            <div>
                                <label for="sucursal" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Sucursal</label>
                                <select name="sucursal" id="sucursal" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
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

                            {{-- Puesto --}}
                            <div>
                                <label for="puesto" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Puesto</label>
                                <select name="puesto" id="puesto" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
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
                                <label for="departamento" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Departamento</label>
                                <select name="departamento" id="departamento" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todos</option>
                                    @foreach($departamentos as $depto)
                                        <option value="{{ $depto->id }}" {{ request('departamento') == $depto->id ? 'selected' : '' }}>
                                            {{ $depto->nombre_depto }} {{ $depto->estado == 1 ? '(Activo)' : '(Inactivo)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Estado --}}
                            <div>
                                <label for="estado" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Estado del empleado</label>
                                <select name="estado" id="estado" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activos</option>
                                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivos</option>
                                </select>
                            </div>

                            {{-- Login --}}
                            <div>
                                <label for="login" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Acceso al sistema</label>
                                <select name="login" id="login" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('login') === '1' ? 'selected' : '' }}>Sí (tienen login)</option>
                                    <option value="0" {{ request('login') === '0' ? 'selected' : '' }}>No (sin login)</option>
                                </select>
                            </div>

                            {{-- Rol --}}
                            <div>
                                <label for="rol" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Rol de sistema</label>
                                <select name="rol" id="rol" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-white transition-colors">
                                    <option value="">Todos</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}" {{ request('rol') == $rol->id ? 'selected' : '' }}>
                                            {{ $rol->rol_name ?? $rol->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        {{-- Botones --}}
                        <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm text-sm flex items-center justify-center transition-all active:scale-95">
                                <i class="fa-solid fa-magnifying-glass mr-2"></i> Filtrar resultados
                            </button>
                            <button type="button" onclick="openPdfModal()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm text-sm flex items-center justify-center transition-all active:scale-95">
                                <i class="fa-solid fa-file-pdf mr-2"></i> Exportar a PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de resultados --}}
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                
                <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-black text-gray-900 text-lg leading-tight">Directorio de empleados</h3>
                    <div class="text-sm font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg border border-blue-100">
                        {{ isset($empleados) ? count($empleados) : 0 }} registros
                    </div>
                </div>

                <div class="p-6 overflow-x-auto">
                    <table id="tablaFiltros" class="min-w-full divide-y divide-gray-200 display nowrap" style="width:100%">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Cód.</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Nombre completo</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Sucursal</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Área / puesto</th>
                                {{-- Horarios --}}
                                <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Horarios asignados</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Rol de sistema</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Acceso</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($empleados ?? [] as $empleado)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-600">{{ $empleado->cod_trabajador ?? 'N/A' }}</td>
                                    
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shadow-inner">
                                                {{ substr($empleado->nombres, 0, 1) }}{{ substr($empleado->apellidos, 0, 1) }}
                                            </div>
                                            <div class="text-sm font-bold text-gray-800">{{ $empleado->nombres }} {{ $empleado->apellidos }}</div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 font-medium">
                                        <i class="fa-solid fa-store text-gray-400 text-xs mr-1"></i> {{ $empleado->sucursal->nombre ?? 'N/A' }}
                                    </td>
                                    
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-700">{{ $empleado->puesto->desc_puesto ?? 'N/A' }}</div>
                                        <div class="text-[10px] text-gray-500 uppercase">{{ $empleado->departamento->nombre_depto ?? 'N/A' }}</div>
                                    </td>

                                    {{-- Horarios --}}
                                    <td class="px-4 py-3 whitespace-normal min-w-[160px]">
                                        {{-- 1. Horarios presenciales --}}
                                        @if($empleado->horarios && $empleado->horarios->isNotEmpty())
                                            @php
                                                $horariosUnicos = $empleado->horarios->unique('id');
                                            @endphp
                                            <div class="space-y-1">
                                                @foreach($horariosUnicos as $horario)
                                                    @php
                                                        $dias = is_array($horario->dias) ? $horario->dias : json_decode($horario->dias, true);
                                                        $diasStr = implode(', ', array_map(function($d) { 
                                                            return mb_convert_case(mb_substr(trim($d), 0, 3, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'); 
                                                        }, $dias ?? []));
                                                    @endphp
                                                    <div class="bg-gray-50 border border-gray-100 rounded px-2 py-1 shadow-sm">
                                                        <div class="text-[10px] font-black text-gray-700 font-mono">
                                                            {{ \Carbon\Carbon::parse($horario->hora_ini)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}
                                                        </div>
                                                        <div class="text-[9px] text-blue-600 font-bold uppercase mt-0.5">{{ $diasStr }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No asignado</span>
                                        @endif

                                        {{-- 2. Trabajo remoto --}}
                                        @php
                                            $esRemotoActivo = false;
                                            $diasRemotoStr = '';
                                            $configRemoto = $empleado->trabajo_remoto;

                                            if ($configRemoto) {
                                                $hoy = \Carbon\Carbon::today();
                                                $inicio = \Carbon\Carbon::parse($configRemoto->fecha_inicio)->startOfDay();
                                                $fin = $configRemoto->fecha_fin ? \Carbon\Carbon::parse($configRemoto->fecha_fin)->startOfDay() : null;

                                                if ($hoy->greaterThanOrEqualTo($inicio) && ($fin === null || $hoy->lessThanOrEqualTo($fin))) {
                                                    $esRemotoActivo = true;
                                                    $diasArr = is_array($configRemoto->dias) ? $configRemoto->dias : json_decode($configRemoto->dias, true);
                                                    
                                                    if(is_array($diasArr)) {
                                                        $diasRemotoStr = implode(', ', array_map(function($d) {
                                                            return mb_convert_case(mb_substr(trim($d), 0, 3, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                                                        }, $diasArr));
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if($esRemotoActivo && !empty($diasRemotoStr))
                                            <div class="mt-1.5 bg-purple-50 border border-purple-200 rounded px-2 py-1 shadow-sm flex flex-col">
                                                <div class="text-[9px] font-black text-purple-700 uppercase flex items-center gap-1">
                                                    <i class="fa-solid fa-house-laptop"></i> Remoto
                                                </div>
                                                <div class="text-[9px] text-purple-600 font-bold uppercase">{{ $diasRemotoStr }}</div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-600">
                                        @if($empleado->login == 1 && $empleado->user && $empleado->user->rol)
                                            <span class="bg-gray-100 border border-gray-200 px-2 py-1 rounded text-xs">{{ $empleado->user->rol->rol_name ?? $empleado->user->rol->name }}</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($empleado->login == 1)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">SÍ</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">NO</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($empleado->estado == 1)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700"><i class="fa-solid fa-circle text-[8px] mr-1.5 text-green-500"></i> ACTIVO</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700"><i class="fa-solid fa-circle text-[8px] mr-1.5 text-red-500"></i> INACTIVO</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación de PDF --}}
    <div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closePdfModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-50 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-file-pdf text-red-500 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-black text-gray-900" id="modal-title">Exportar a PDF</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Se generará un documento formal del directorio con los filtros actuales. Se abrirá en una nueva pestaña.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" id="btnConfirmarPdf" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-bold text-white hover:bg-red-700 sm:w-auto sm:text-sm transition-colors">Generar documento</button>
                    <button type="button" onclick="closePdfModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm transition-colors">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPdfModal() { document.getElementById('pdfModal').classList.remove('hidden'); }
            function closePdfModal() { document.getElementById('pdfModal').classList.add('hidden'); }

            document.getElementById('btnConfirmarPdf').addEventListener('click', function() {
                const params = window.location.search;
                const baseUrl = "{{ route('empleados.pdf') }}";
                window.open(baseUrl + params, '_blank');
                closePdfModal();
            });
        </script>
        
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
        <style>
            .dt-container { font-family: inherit; font-size: 0.875rem; }
            .dt-paging-button { border-radius: 0.5rem !important; }
        </style>
    @endpush
</x-app-layout>