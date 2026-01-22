<x-app-layout title="Permisos">

    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Permisos') }}
        </h2>
    </x-slot>

    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Alertas Compactas --}}
            @if (session('success'))
                <div
                    class="mb-4 px-4 py-2 rounded-lg bg-green-50 border border-green-200 text-green-700 shadow-sm flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-check-circle"></i>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            @elseif (session('error'))
                <div
                    class="mb-4 px-4 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 shadow-sm flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">

                {{-- Toolbar Compacto --}}
                <div
                    class="px-4 py-3 border-b border-gray-100 bg-white flex flex-col md:flex-row items-center justify-between gap-3">
                    <div class="relative w-full md:w-80 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" id="buscadorEmpleados" placeholder="Buscar empleado..."
                            class="block w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <a href="{{ route('permisos.create') }}"
                        class="inline-flex items-center px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-lg shadow-sm transition-all">
                        <i class="fa-solid fa-plus mr-1.5"></i> Nuevo
                    </a>
                </div>

                {{-- Contenedor de Listas --}}
                <div class="p-4 bg-gray-50 space-y-3">

                    @foreach($sucursales as $index => $sucursal)
                        <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" x-ref="accordion"
                            class="bg-white border border-gray-200 rounded-lg shadow-sm sucursal-accordion">

                            {{-- Header Acordeón Compacto --}}
                            <button type="button"
                                class="w-full flex justify-between items-center px-4 py-2.5 text-left hover:bg-gray-50 transition-colors"
                                @click="open = !open">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-building text-blue-500 text-sm"></i>
                                    <span class="text-gray-800 font-bold text-sm">{{ $sucursal->nombre }}</span>
                                    <span class="text-xs text-gray-400">({{ $sucursal->empleados->count() }})</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-gray-400 text-xs transition-transform duration-200"
                                    :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            {{-- Contenido Acordeón --}}
                            <div x-show="open" x-collapse class="border-t border-gray-100 sucursal-content">
                                <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                    @foreach($sucursal->empleados as $empleado)

                                        {{-- CARD COMPACTA --}}
                                        <div class="empleado-card group bg-white rounded-lg border border-gray-200 p-3 hover:shadow-md hover:border-blue-400 transition-all duration-200 cursor-pointer"
                                            data-nombre="{{ $empleado->nombres }} {{ $empleado->apellidos }}"
                                            data-codigo="{{ $empleado->cod_trabajador }}" x-data="{ showModal: false }"
                                            @click="showModal = true">

                                            <div class="flex items-center gap-3 mb-2">
                                                {{-- Avatar pequeño corregido --}}
                                                <div
                                                    class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-xs border border-gray-200 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors uppercase">
                                                    {{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->nombres)), 0, 1) }}{{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->apellidos)), 0, 1) }}
                                                </div>
                                                <div class="overflow-hidden">
                                                    <h4 class="text-gray-900 font-bold text-sm truncate leading-tight"
                                                        title="{{ $empleado->nombres }} {{ $empleado->apellidos }}">
                                                        {{ $empleado->nombres }}
                                                    </h4>
                                                    <p class="text-gray-500 text-[10px] uppercase tracking-wide">
                                                        {{ $empleado->apellidos }}</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-50">
                                                <div class="flex gap-1">
                                                    @php
                                                        $activos = $empleado->permisos->where('estado', 1)->count();
                                                        $inactivos = $empleado->permisos->where('estado', 0)->count();
                                                    @endphp

                                                    @if($activos > 0)
                                                        <span
                                                            class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded text-[10px] font-bold flex items-center">
                                                            <div class="w-1 h-1 bg-emerald-500 rounded-full mr-1"></div>
                                                            {{ $activos }}
                                                        </span>
                                                    @endif
                                                    @if($inactivos > 0)
                                                        <span
                                                            class="px-1.5 py-0.5 bg-gray-50 text-gray-600 border border-gray-200 rounded text-[10px] font-bold flex items-center">
                                                            <div class="w-1 h-1 bg-gray-400 rounded-full mr-1"></div>
                                                            {{ $inactivos }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <span class="text-blue-600 text-[10px] font-bold uppercase tracking-tight">
                                                    Ver detalles <i class="fa-solid fa-chevron-right ml-0.5"></i>
                                                </span>
                                            </div>

                                            {{-- MODAL CON X-TELEPORT (SOLUCIÓN AL BUG) --}}
                                            <template x-teleport="body">
                                                <div x-show="showModal" style="display: none;"
                                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/40 backdrop-blur-[2px] p-4"
                                                    x-transition:enter="ease-out duration-200"
                                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                                    x-transition:leave="ease-in duration-150"
                                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                                                    {{-- Añadimos @click.stop aquí para que clics internos no afecten a la card
                                                    --}}
                                                    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col transform transition-all"
                                                        @click.away="showModal = false" @click.stop>

                                                        {{-- Header Modal --}}
                                                        <div
                                                            class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-lg">
                                                            <div class="flex items-center gap-2">
                                                                <div
                                                                    class="w-7 h-7 rounded bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold uppercase">
                                                                    {{ substr(preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $empleado->nombres)), 0, 1) }}
                                                                </div>
                                                                <div>
                                                                    <h3 class="text-sm font-bold text-gray-900">
                                                                        {{ $empleado->nombres }} {{ $empleado->apellidos }}</h3>
                                                                    <p class="text-[10px] text-gray-500 uppercase">
                                                                        {{ $empleado->cod_trabajador }}</p>
                                                                </div>
                                                            </div>
                                                            <button @click="showModal = false"
                                                                class="text-gray-400 hover:text-gray-600 p-1">
                                                                <i class="fa-solid fa-xmark text-lg"></i>
                                                            </button>
                                                        </div>

                                                        {{-- Body Modal --}}
                                                        <div class="p-4 overflow-y-auto custom-scrollbar bg-gray-50/30">
                                                            @forelse($empleado->permisos as $permiso)
                                                                            <div
                                                                                class="bg-white border border-gray-200 rounded-lg p-3 mb-2 shadow-sm hover:border-blue-300 transition-colors">
                                                                                <div class="flex justify-between items-start gap-3">
                                                                                    <div class="flex-1">
                                                                                        <div class="flex items-center gap-2 mb-1">
                                                                                            <span
                                                                                                class="font-bold text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded uppercase">
                                                                                                {{ $permiso->tipo->nombre }}
                                                                                            </span>
                                                                                            <span
                                                                                                class="w-1.5 h-1.5 rounded-full {{ $permiso->estado ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                                                                        </div>
                                                                                        <p
                                                                                            class="text-gray-600 text-xs italic mb-1.5 leading-snug">
                                                                                            "{{ $permiso->motivo }}"</p>

                                                                                        <div
                                                                                            class="flex flex-wrap gap-2 text-[11px] text-gray-500">
                                                                                            @if($permiso->fecha_inicio)
                                                                                                <span
                                                                                                    class="flex items-center bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100">
                                                                                                    <i class="fa-regular fa-calendar mr-1"></i>
                                                                                                    {{ $permiso->fecha_inicio }}
                                                                                                </span>
                                                                                                -
                                                                                                <span
                                                                                                    class="flex items-center bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100">
                                                                                                    <i class="fa-regular fa-calendar mr-1"></i>
                                                                                                    {{ $permiso->fecha_fin }}
                                                                                                </span>
                                                                                            @endif
                                                                                            @if($permiso->valor)
                                                                                                <span
                                                                                                    class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded">
                                                                                                    <i class="fa-regular fa-clock mr-1"></i>
                                                                                                    {{ $permiso->valor }} mins
                                                                                                </span>
                                                                                            @endif
                                                                                            @if($permiso->cantidad_mts)
                                                                                                <span
                                                                                                    class="flex items-center bg-gray-100 px-1.5 py-0.5 rounded">
                                                                                                    <i class="fa-solid fa-arrows-left-right mr-1"></i>
                                                                                                    {{ $permiso->cantidad_mts }} mts
                                                                                                </span>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>

                                                                                    {{-- Acciones --}}
                                                                                    <div class="flex flex-col gap-1">
                                                                                        {{-- Usamos @click.stop para que no interfiera el clic
                                                                                        de la card al editar --}}
                                                                                        <a href="{{ route('permisos.edit', $permiso->id) }}"
                                                                                            @click.stop
                                                                                            class="w-6 h-6 flex items-center justify-center text-blue-600 bg-blue-50 hover:bg-blue-100 rounded transition-colors text-xs">
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
                                                                <div class="text-center py-6 text-xs text-gray-400">Sin permisos
                                                                    registrados</div>
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
    </div>

    {{-- Script de búsqueda (Igual que antes, funciona perfecto) --}}
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