<x-app-layout title="Permisos">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permisos de Empleados') }}
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
                <div class="flex items-center mb-6">
                    {{-- Buscador centrado --}}
                    <div class="flex-1 flex justify-center">
                        <input type="text" id="buscadorEmpleados" placeholder="Buscar.."
                            class="w-2/3 md:w-1/4 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
                    </div>

                    {{-- Botón a la derecha --}}
                    <div class="ml-4 flex-shrink-0">
                        <a href="{{ route('permisos.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            + Añadir nuevo
                        </a>
                    </div>
                </div>

                {{-- Acordeones de sucursales --}}
                <div class="space-y-4">

                    @foreach($sucursales as $index => $sucursal)
                        <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" x-ref="accordion"
                            class="bg-white border border-gray-200 rounded-lg shadow sucursal-accordion">

                            {{-- Header del acordeón --}}
                            <button type="button"
                                class="w-full flex justify-between items-center px-5 py-4 text-left font-semibold text-gray-700 hover:bg-gray-50"
                                @click="open = !open">
                                <span>
                                    {{ $sucursal->nombre }}
                                    <span class="ml-2 text-sm text-gray-500">
                                        ({{ $sucursal->empleados->count() }} empleados)
                                    </span>
                                </span>

                                <i class="fa-solid fa-chevron-down transition-transform duration-200"
                                    :class="open ? 'rotate-180' : ''">
                                </i>
                            </button>

                            {{-- Contenido del acordeón --}}
                            <div x-show="open" x-collapse class="px-5 pb-5 sucursal-content">
                                {{-- Cards de empleados --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach($sucursal->empleados as $empleado)
                                        <div class="empleado-card bg-white shadow rounded-lg p-4 border border-gray-200 flex flex-col justify-between"
                                            data-nombre="{{ $empleado->nombres }} {{ $empleado->apellidos }}"
                                            data-codigo="{{ $empleado->cod_trabajador }}">

                                            <div>
                                                <p class="text-gray-800 font-semibold text-lg">
                                                    {{ $empleado->nombres }} {{ $empleado->apellidos }}
                                                </p>
                                                <p class="text-gray-500 text-sm">
                                                    Código: {{ $empleado->cod_trabajador }}
                                                </p>
                                            </div>

                                            <div class="mt-4 flex justify-between items-center">
                                                <div class="flex gap-2 text-xs">
                                                    @php
                                                        $activos = $empleado->permisos->where('estado', 1)->count();
                                                        $inactivos = $empleado->permisos->where('estado', 0)->count();
                                                    @endphp

                                                    @if($activos > 0)
                                                        <span
                                                            class="px-2 py-1 bg-green-100 text-green-800 rounded-full font-medium">
                                                            {{ $activos }} activo{{ $activos > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif

                                                    @if($inactivos > 0)
                                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full font-medium">
                                                            {{ $inactivos }} inactivo{{ $inactivos > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <button onclick="openModal('modal-{{ $empleado->id }}')"
                                                    class="px-3 py-1 text-white text-sm rounded-lg hover:opacity-90"
                                                    style="background-color: #67d1ff;">
                                                    Ver permisos
                                                </button>
                                            </div>
                                        </div>


                                        {{-- Modal de permisos --}}
                                        <div id="modal-{{ $empleado->id }}"
                                            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto">
                                            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6 relative mx-4 my-12">
                                                <button onclick="closeModal('modal-{{ $empleado->id }}')"
                                                    class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">
                                                    <i class="fa-solid fa-xmark text-lg"></i>
                                                </button>
                                                <h3 class="text-lg font-semibold mb-4 text-center">Permisos de
                                                    {{ $empleado->nombres }}
                                                    {{ $empleado->apellidos }}
                                                </h3>

                                                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                                                    @forelse($empleado->permisos as $permiso)
                                                        <div class="border rounded-lg p-3 bg-gray-50 w-full flex flex-col gap-2">
                                                            <div
                                                                class="flex flex-col md:flex-row md:justify-between md:items-start gap-2">
                                                                <div class="flex-1 space-y-1">
                                                                    <p><span class="font-semibold">Tipo:</span>
                                                                        {{ $permiso->tipo->nombre }}</p>
                                                                    <p><span class="font-semibold">Motivo:</span>
                                                                        {{ $permiso->motivo }}</p>
                                                                    @if($permiso->valor || $permiso->cantidad_mts)
                                                                        <p><span class="font-semibold">Valor/Mts:</span>
                                                                            @if($permiso->valor) {{ $permiso->valor }} mins
                                                                            @else {{ $permiso->cantidad_mts }} mts @endif
                                                                        </p>
                                                                    @endif
                                                                    @if($permiso->fecha_inicio || $permiso->fecha_fin)
                                                                        <p><span class="font-semibold">Fechas:</span>
                                                                            {{ $permiso->fecha_inicio ?? '-' }}
                                                                            @if($permiso->fecha_fin) - {{ $permiso->fecha_fin }} @endif
                                                                        </p>
                                                                    @endif
                                                                    @if($permiso->dias_activa)
                                                                        <p><span class="font-semibold">Días activo:</span>
                                                                            {{ $permiso->dias_activa }}</p>
                                                                    @endif
                                                                    <p><span class="font-semibold">Estado:</span>
                                                                        @if($permiso->estado) Activo
                                                                        @else Inactivo @endif</p>
                                                                </div>

                                                                <div class="flex-shrink-0 flex items-start gap-2 mt-2 md:mt-0">
                                                                    <a href="{{ route('permisos.edit', $permiso->id) }}"
                                                                        class="text-blue-600 hover:underline" title="Editar">
                                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                                    </a>
                                                                    <form action="{{ route('permisos.delete', $permiso->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('¿Seguro que deseas eliminar este permiso?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="text-red-600 hover:underline"
                                                                            title="Inactivar">
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p class="text-gray-500">No tiene permisos registrados.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div id="mensajeSinResultados" class="hidden text-center py-4 text-gray-500">
                        <p>No se encontraron resultados.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('flex');
            document.getElementById(id).classList.add('hidden');
        }
        const buscador = document.getElementById('buscadorEmpleados');
        const acordeones = document.querySelectorAll('.sucursal-accordion');

        // FUNCIÓN CORREGIDA: Detecta la versión de Alpine y fuerza el cambio
        function setAccordion(acordeon, estadoOpen) {
            if (acordeon._x_dataStack) {
                //  Para Alpine.js v3
                acordeon._x_dataStack[0].open = estadoOpen;
            } else if (acordeon.__x) {
                // Para Alpine.js v2 (Antiguo)
                acordeon.__x.$data.open = estadoOpen;
            } else {
                // Fallback: Si no detecta Alpine, intenta buscar el botón y hacerle click si es necesario
                // Esto es útil si la estructura HTML es muy compleja
                console.warn('No se detectó la instancia de Alpine en:', acordeon);
            }
        }

        buscador.addEventListener('input', function () {
            const texto = this.value.toLowerCase().trim();

            //  1. SI EL BUSCADOR ESTÁ VACÍO
            if (texto === '') {
                acordeones.forEach((acordeon, index) => {
                    acordeon.classList.remove('hidden');

                    // Muestra todas las tarjetas internas
                    acordeon.querySelectorAll('.empleado-card').forEach(card => {
                        card.classList.remove('hidden');
                    });

                    // Resetea estado: Abre el primero, cierra los demás
                    setAccordion(acordeon, index === 0);
                });
                return;
            }
            let totalCoincidenciasGlobales = 0;
            // 2. SI HAY TEXTO (BUSCAR)
            acordeones.forEach(acordeon => {
                const cards = acordeon.querySelectorAll('.empleado-card');
                let coincidenciasEnGrupo = 0;

                cards.forEach(card => {
                    // Usamos '|| ""' para evitar error si el dataset está vacío
                    const nombre = (card.dataset.nombre || '').toLowerCase();
                    const codigo = (card.dataset.codigo || '').toLowerCase();

                    if (nombre.includes(texto) || codigo.includes(texto)) {
                        card.classList.remove('hidden');
                        coincidenciasEnGrupo++;
                    } else {
                        card.classList.add('hidden');
                    }
                });
                totalCoincidenciasGlobales += coincidenciasEnGrupo;
                if (coincidenciasEnGrupo > 0) {
                    // HAY COINCIDENCIA:
                    // 1. Quitamos la clase hidden del contenedor principal
                    acordeon.classList.remove('hidden');
                    // 2. Forzamos a Alpine a poner la variable 'open' en true
                    setAccordion(acordeon, true);
                } else {
                    // NO HAY COINCIDENCIA:
                    acordeon.classList.add('hidden');
                    setAccordion(acordeon, false);
                }
            });
            if (totalCoincidenciasGlobales === 0) {
                mensajeSinResultados.classList.remove('hidden');
            } else {
                mensajeSinResultados.classList.add('hidden');
            }
        });
    </script>

</x-app-layout>