<x-app-layout title="Permisos">

    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Permisos') }}
        </h2>
    </x-slot>

    {{-- 🌟 ESTADO GLOBAL DE ALPINE PARA LOS MODALES DE APROBACIÓN --}}
    <div class="py-6 bg-gray-50 min-h-screen" 
         x-data="{ 
            showProcessModal: false, 
            processAction: '', 
            processFormUrl: '', 
            processName: '',
            processTipo: ''
         }">
         
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Alertas Compactas --}}
            @if (session('success'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 shadow-sm flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-check-circle"></i>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            @elseif (session('error'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 shadow-sm flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            {{-- =========================================================================
                 🌟 SECCIÓN 1: SOLICITUDES PENDIENTES (APP MÓVIL)
                 ========================================================================= --}}
            @if($pendientes->count() > 0)
                <div class="mb-8">
                    <h3 class="text-lg font-black text-gray-800 mb-4 flex items-center gap-2 tracking-tight">
                        <div class="w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-bell animate-pulse"></i> 
                        </div>
                        Por Aprobar
                        <span class="bg-red-500 text-white text-xs px-2.5 py-0.5 rounded-full shadow-sm">{{ $pendientes->count() }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($pendientes as $pendiente)
                            <div class="bg-white rounded-xl border border-yellow-200 shadow-sm overflow-hidden flex flex-col relative">
                                
                                {{-- Cabecera Tarjeta Pendiente --}}
                                <div class="bg-yellow-50/50 px-4 py-3 border-b border-yellow-100 flex justify-between items-start">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $pendiente->empleado->nombres)), 0, 1) }}{{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $pendiente->empleado->apellidos)), 0, 1) }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 leading-tight">{{ $pendiente->empleado->nombres }}</h4>
                                            <p class="text-[10px] text-gray-500 uppercase">{{ $pendiente->empleado->sucursal->nombre }}</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400">{{ ucfirst($pendiente->created_at->locale('es')->isoFormat('DD MMM')) }}</span>
                                </div>

                                {{-- Cuerpo Tarjeta Pendiente --}}
                                <div class="p-4 flex-grow">
                                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 text-[10px] font-bold uppercase rounded mb-2">
                                        {{ $pendiente->tipo->nombre ?? 'General' }}
                                    </span>
                                    <p class="text-xs text-gray-600 italic mb-3 line-clamp-2">"{{ $pendiente->motivo }}"</p>
                                    
                                    {{-- Detalles extra --}}
                                    <div class="flex flex-wrap gap-2 text-[10px] text-gray-500 mb-2">
                                        @if($pendiente->fecha_inicio)
                                            <span class="flex items-center bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100">
                                                <i class="fa-regular fa-calendar mr-1"></i> 
                                                {{ \Carbon\Carbon::parse($pendiente->fecha_inicio)->format('d/m') }} 
                                                @if($pendiente->fecha_inicio != $pendiente->fecha_fin)
                                                    - {{ \Carbon\Carbon::parse($pendiente->fecha_fin)->format('d/m') }}
                                                @endif
                                            </span>
                                        @endif
                                        
                                        {{-- 🌟 NUEVO: HORARIO DEL PERMISO --}}
                                        @if($pendiente->hora_ini && $pendiente->hora_fin)
                                            <span class="flex items-center bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border border-indigo-100">
                                                <i class="fa-regular fa-clock mr-1"></i> 
                                                {{ \Carbon\Carbon::parse($pendiente->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($pendiente->hora_fin)->format('H:i') }}
                                            </span>
                                        @endif

                                        @if($pendiente->valor)
                                            <span class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">
                                                <i class="fa-regular fa-clock mr-1"></i> {{ $pendiente->valor }} min
                                            </span>
                                        @endif
                                        
                                        @if($pendiente->cantidad_mts)
                                            <span class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">
                                                <i class="fa-solid fa-arrows-left-right mr-1"></i> {{ $pendiente->cantidad_mts }} mts
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Botones de Acción --}}
                                <div class="grid grid-cols-2 border-t border-gray-100">
                                    <button @click="showProcessModal = true; processAction = 'rechazar'; processFormUrl = '{{ route('permisos.procesar', $pendiente->id) }}'; processName = '{{ $pendiente->empleado->nombres }} {{ $pendiente->empleado->apellidos }}'; processTipo = '{{ $pendiente->tipo->nombre }}';" 
                                            class="py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors border-r border-gray-100">
                                        <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                                    </button>
                                    <button @click="showProcessModal = true; processAction = 'aprobar'; processFormUrl = '{{ route('permisos.procesar', $pendiente->id) }}'; processName = '{{ $pendiente->empleado->nombres }} {{ $pendiente->empleado->apellidos }}'; processTipo = '{{ $pendiente->tipo->nombre }}';" 
                                            class="py-2.5 text-xs font-bold text-emerald-600 hover:bg-emerald-50 transition-colors">
                                        <i class="fa-solid fa-check mr-1"></i> Aprobar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- =========================================================================
                 SECCIÓN 2: DIRECTORIO NORMAL (ACORDEONES)
                 ========================================================================= --}}
            <div class="mb-4 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-gray-400"></i>
                <h3 class="text-lg font-black text-gray-800 tracking-tight">Directorio de Permisos</h3>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">

                {{-- Toolbar Compacto --}}
                <div class="px-4 py-3 border-b border-gray-100 bg-white flex flex-col md:flex-row items-center justify-between gap-3">
                    <div class="relative w-full md:w-80 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" id="buscadorEmpleados" placeholder="Buscar empleado..."
                            class="block w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <a href="{{ route('permisos.create') }}"
                        class="inline-flex items-center px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-lg shadow-sm transition-all">
                        <i class="fa-solid fa-plus mr-1.5"></i> Nuevo Permiso
                    </a>
                </div>

                {{-- Contenedor de Listas --}}
                <div class="p-4 bg-gray-50 space-y-3">

                    @foreach($sucursales as $index => $sucursal)
                        <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" x-ref="accordion"
                            class="bg-white border border-gray-200 rounded-lg shadow-sm sucursal-accordion">

                            {{-- Header Acordeón Compacto --}}
                            <button type="button" class="w-full flex justify-between items-center px-4 py-2.5 text-left hover:bg-gray-50 transition-colors" @click="open = !open">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-building text-blue-500 text-sm"></i>
                                    <span class="text-gray-800 font-bold text-sm">{{ $sucursal->nombre }}</span>
                                    <span class="text-xs text-gray-400">({{ $sucursal->empleados->count() }})</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-gray-400 text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            {{-- Contenido Acordeón --}}
                            <div x-show="open" x-collapse class="border-t border-gray-100 sucursal-content">
                                <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                    @foreach($sucursal->empleados as $empleado)

                                        {{-- CARD COMPACTA EMPLEADO --}}
                                        <div class="empleado-card group bg-white rounded-lg border border-gray-200 p-3 hover:shadow-md hover:border-blue-400 transition-all duration-200 cursor-pointer"
                                            data-nombre="{{ $empleado->nombres }} {{ $empleado->apellidos }}"
                                            data-codigo="{{ $empleado->cod_trabajador }}" x-data="{ showModal: false }"
                                            @click="showModal = true">

                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-xs border border-gray-200 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors uppercase">
                                                    {{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->nombres)), 0, 1) }}{{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->apellidos)), 0, 1) }}
                                                </div>
                                                <div class="overflow-hidden">
                                                    <h4 class="text-gray-900 font-bold text-sm truncate leading-tight" title="{{ $empleado->nombres }} {{ $empleado->apellidos }}">
                                                        {{ $empleado->nombres }}
                                                    </h4>
                                                    <p class="text-gray-500 text-[10px] uppercase tracking-wide">{{ $empleado->apellidos }}</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-50">
                                                <div class="flex gap-1">
                                                    @php
                                                        $activos = $empleado->permisos->where('estado', 1)->count();
                                                        $inactivos = $empleado->permisos->where('estado', 0)->count();
                                                    @endphp

                                                    @if($activos > 0)
                                                        <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[10px] font-bold flex items-center">
                                                            <div class="w-1 h-1 bg-emerald-500 rounded-full mr-1"></div> {{ $activos }}
                                                        </span>
                                                    @endif
                                                    @if($inactivos > 0)
                                                        <span class="px-1.5 py-0.5 bg-gray-50 text-gray-600 border border-gray-200 rounded text-[10px] font-bold flex items-center">
                                                            <div class="w-1 h-1 bg-gray-400 rounded-full mr-1"></div> {{ $inactivos }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-blue-600 text-[10px] font-bold uppercase tracking-tight">
                                                    Ver detalles <i class="fa-solid fa-chevron-right ml-0.5"></i>
                                                </span>
                                            </div>

                                            {{-- MODAL DETALLE EMPLEADO --}}
                                            <template x-teleport="body">
                                                <div x-show="showModal" style="display: none;"
                                                    class="fixed inset-0 z-[9990] flex items-center justify-center bg-gray-900/40 backdrop-blur-[2px] p-4"
                                                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                                                    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col transform transition-all"
                                                        @click.away="showModal = false" @click.stop>

                                                        {{-- Header Modal --}}
                                                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-lg">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-7 h-7 rounded bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold uppercase">
                                                                    {{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->nombres)), 0, 1) }}
                                                                </div>
                                                                <div>
                                                                    <h3 class="text-sm font-bold text-gray-900">{{ $empleado->nombres }} {{ $empleado->apellidos }}</h3>
                                                                    <p class="text-[10px] text-gray-500 uppercase">{{ $empleado->cod_trabajador }}</p>
                                                                </div>
                                                            </div>
                                                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1">
                                                                <i class="fa-solid fa-xmark text-lg"></i>
                                                            </button>
                                                        </div>

                                                        {{-- Body Modal --}}
                                                        <div class="p-4 overflow-y-auto custom-scrollbar bg-gray-50/30">
                                                            @forelse($empleado->permisos as $permiso)
                                                                <div class="bg-white border border-gray-200 rounded-lg p-3 mb-2 shadow-sm hover:border-blue-300 transition-colors">
                                                                    <div class="flex justify-between items-start gap-3">
                                                                        <div class="flex-1">
                                                                            <div class="flex items-center gap-2 mb-1">
                                                                                <span class="font-bold text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded uppercase">
                                                                                    {{ $permiso->tipo->nombre ?? 'General' }}
                                                                                </span>
                                                                                <span class="w-1.5 h-1.5 rounded-full {{ $permiso->estado ? 'bg-green-500' : 'bg-gray-400' }}" title="{{ $permiso->estado ? 'Activo' : 'Inactivo' }}"></span>
                                                                                
                                                                                {{-- Etiqueta de Origen --}}
                                                                                @if($permiso->app_creacion == 2)
                                                                                    <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-100 px-1 rounded" title="Solicitado por el empleado">APP</span>
                                                                                @endif
                                                                            </div>
                                                                            <p class="text-gray-600 text-xs italic mb-1.5 leading-snug">"{{ $permiso->motivo }}"</p>

                                                                            <div class="flex flex-wrap gap-2 text-[11px] text-gray-500">
                                                                                @if($permiso->fecha_inicio)
                                                                                    <span class="flex items-center bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100">
                                                                                        <i class="fa-regular fa-calendar mr-1"></i> 
                                                                                        {{ \Carbon\Carbon::parse($permiso->fecha_inicio)->format('d/m/Y') }} 
                                                                                        @if($permiso->fecha_inicio != $permiso->fecha_fin)
                                                                                            - {{ \Carbon\Carbon::parse($permiso->fecha_fin)->format('d/m/Y') }}
                                                                                        @endif
                                                                                    </span>
                                                                                @endif

                                                                                {{-- 🌟 NUEVO: HORARIO DEL PERMISO --}}
                                                                                @if($permiso->hora_ini && $permiso->hora_fin)
                                                                                    <span class="flex items-center bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border border-indigo-100">
                                                                                        <i class="fa-regular fa-clock mr-1"></i> 
                                                                                        {{ \Carbon\Carbon::parse($permiso->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') }}
                                                                                    </span>
                                                                                @endif

                                                                                @if($permiso->valor)
                                                                                    <span class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">
                                                                                        <i class="fa-regular fa-clock mr-1"></i> {{ $permiso->valor }} mins
                                                                                    </span>
                                                                                @endif

                                                                                @if($permiso->cantidad_mts)
                                                                                    <span class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">
                                                                                        <i class="fa-solid fa-arrows-left-right mr-1"></i> {{ $permiso->cantidad_mts }} mts
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        {{-- Acciones --}}
                                                                        <div class="flex flex-col gap-1">
                                                                            <a href="{{ route('permisos.edit', $permiso->id) }}" @click.stop class="w-6 h-6 flex items-center justify-center text-blue-600 bg-blue-50 hover:bg-blue-100 rounded transition-colors text-xs">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </a>
                                                                            <button type="button" @click.stop="showModal = false; $dispatch('open-confirm-modal', { 
                                                                                url: '{{ route('permisos.delete', $permiso->id) }}',
                                                                                title: '¿Eliminar permiso?',
                                                                                message: 'Esta acción es irreversible.',
                                                                                buttonText: 'Eliminar'
                                                                            })" class="w-6 h-6 flex items-center justify-center text-red-600 bg-red-50 hover:bg-red-100 rounded transition-colors text-xs">
                                                                                <i class="fa-solid fa-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="text-center py-6 text-xs text-gray-400">Sin permisos registrados</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div id="mensajeSinResultados" class="hidden text-center py-8">
                        <p class="text-sm text-gray-500">No se encontraron empleados.</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================================================================
             🌟 MODAL DE PROCESAMIENTO (APROBAR / RECHAZAR) CON TELEPORT
             ========================================================================= --}}
        <template x-teleport="body">
            <div x-show="showProcessModal" style="display: none;"
                 class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
                 x-transition.opacity>
                
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all overflow-hidden" @click.away="showProcessModal = false">
                    
                    {{-- Cabecera dinámica según la acción --}}
                    <div class="px-5 py-4 flex items-center justify-center" :class="processAction === 'aprobar' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                        <i class="text-3xl" :class="processAction === 'aprobar' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                    </div>

                    <div class="p-5 text-center">
                        <h3 class="text-lg font-bold text-gray-900 mb-1" x-text="processAction === 'aprobar' ? '¿Aprobar Permiso?' : '¿Rechazar Permiso?'"></h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Estás a punto de <strong x-text="processAction" :class="processAction === 'aprobar' ? 'text-emerald-600' : 'text-red-600'"></strong> la solicitud de <strong class="text-gray-800" x-text="processTipo"></strong> para <strong class="text-gray-800" x-text="processName"></strong>.
                        </p>

                        <form :action="processFormUrl" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="accion" x-model="processAction">

                            <div class="flex gap-3 mt-6">
                                <button type="button" @click="showProcessModal = false" class="flex-1 bg-gray-100 text-gray-600 font-bold py-2.5 rounded-xl hover:bg-gray-200 transition-colors text-sm">
                                    Cancelar
                                </button>
                                <button type="submit" class="flex-1 text-white font-bold py-2.5 rounded-xl shadow-md transition-transform active:scale-95 text-sm" :class="processAction === 'aprobar' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'" x-text="processAction === 'aprobar' ? 'Sí, Aprobar' : 'Sí, Rechazar'">
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

    </div>

    {{-- Script de búsqueda --}}
    <script>
        const buscador = document.getElementById('buscadorEmpleados');
        const acordeones = document.querySelectorAll('.sucursal-accordion');

        function setAccordion(acordeon, estadoOpen) {
            if (acordeon._x_dataStack) acordeon._x_dataStack[0].open = estadoOpen;
        }

        buscador.addEventListener('input', function () {
            const texto = this.value.toLowerCase().trim();
            const mensajeSinResultados = document.getElementById('mensajeSinResultados');

            if (texto === '') {
                acordeones.forEach((acordeon, index) => {
                    acordeon.classList.remove('hidden');
                    acordeon.querySelectorAll('.empleado-card').forEach(card => card.classList.remove('hidden'));
                    setAccordion(acordeon, index === 0);
                });
                mensajeSinResultados.classList.add('hidden');
                return;
            }

            let totalCoincidencias = 0;
            acordeones.forEach(acordeon => {
                const cards = acordeon.querySelectorAll('.empleado-card');
                let count = 0;
                cards.forEach(card => {
                    const match = (card.dataset.nombre || '').toLowerCase().includes(texto) || (card.dataset.codigo || '').toLowerCase().includes(texto);
                    card.classList.toggle('hidden', !match);
                    if (match) count++;
                });
                totalCoincidencias += count;
                acordeon.classList.toggle('hidden', count === 0);
                if (count > 0) setAccordion(acordeon, true);
            });

            mensajeSinResultados.classList.toggle('hidden', totalCoincidencias > 0);
        });
    </script>
</x-app-layout>