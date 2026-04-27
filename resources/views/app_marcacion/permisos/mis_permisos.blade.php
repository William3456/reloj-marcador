<x-app-layout title="Mis permisos">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight tracking-tight">Mis solicitudes</h2>
        </div>
    </x-slot>

    <div class="py-6 px-4 max-w-md mx-auto mb-20">
        
        {{-- Alerta de éxito --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-4 flex items-start gap-3 shadow-sm transition-all">
                <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-grow">
                    <h3 class="text-sm font-bold text-green-800">¡Éxito!</h3>
                    <p class="text-xs text-green-700 mt-0.5 leading-snug">
                        {{ session('success') }}
                    </p>
                </div>
                <button @click="show = false" class="text-green-500 hover:text-green-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3 shadow-sm transition-all">
                <div class="bg-red-100 text-red-600 rounded-full w-8 h-8 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-grow">
                    <h3 class="text-sm font-bold text-red-800">Acción denegada</h3>
                    <p class="text-xs text-red-700 mt-0.5 leading-snug">
                        {{ session('error') }}
                    </p>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        @endif

        {{-- Botón nueva solicitud --}}
        <div class="mb-6">
            <a href="{{ route('marcacion.permisos.create') }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-2xl shadow-sm transition-transform active:scale-95 flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus"></i> Nueva solicitud
            </a>
        </div>

        <form action="{{ route('marcacion.permisos.index') }}" method="GET" 
              x-data="{ filtroEstado: '{{ $estado_filtro }}' }" 
              class="bg-white p-4 rounded-2xl shadow-sm mb-6 border border-gray-100">
            
            {{-- Selector de estado principal --}}
            <div class="mb-2">
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estado de la solicitud</label>
                <select name="estado_filtro" x-model="filtroEstado" class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    <option value="todos">Mostrar historial por fechas</option>
                    <option value="activos">Solo aprobados / activos</option>
                    <option value="pendientes">Solo en revisión (pendientes)</option>
                    <option value="inactivos">Solo rechazados / inactivos</option>
                </select>
            </div>

            {{-- Bloque ocultable: Fechas y origen --}}
            <div x-show="filtroEstado === 'todos'" x-collapse class="pt-4 border-t border-gray-100 mt-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Creados desde</label>
                        <input type="date" name="desde" value="{{ $desde }}" class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Creados hasta</label>
                        <input type="date" name="hasta" value="{{ $hasta }}" class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Origen del permiso</label>
                    <select name="origen" class="w-full text-sm border-gray-200 rounded-xl focus:ring-blue-500 shadow-sm bg-gray-50">
                        <option value="todos" {{ $origen == 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="mios" {{ $origen == 'mios' ? 'selected' : '' }}>Solicitados por mí</option>
                        <option value="admin" {{ $origen == 'admin' ? 'selected' : '' }}>Asignados por administradores</option>
                    </select>
                </div>
            </div>

            {{-- Fila 3: botones --}}
            <div class="flex gap-3 mt-4">
                <a href="{{ route('marcacion.permisos.index') }}" class="flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-500 rounded-xl hover:bg-gray-200 active:scale-95 transition-all" title="Limpiar filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
                <button type="submit" class="flex-1 bg-gray-800 text-white py-2.5 rounded-xl text-sm font-bold shadow-md active:scale-95 transition-transform flex items-center justify-center">
                    <i class="fa-solid fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>

        <div class="mb-4 px-1 flex flex-wrap items-center gap-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">Viendo:</span>
            
            @if($estado_filtro !== 'todos')
                <span class="inline-flex items-center bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 rounded text-[10px] font-bold shadow-sm">
                    <i class="fa-solid fa-layer-group mr-1.5"></i>
                    {{ $estado_filtro == 'activos' ? 'Aprobados / activos' : ($estado_filtro == 'pendientes' ? 'En revisión' : 'Rechazados / inactivos') }}
                </span>
            @else
                <span class="inline-flex items-center bg-gray-100 text-gray-600 border border-gray-200 px-2 py-0.5 rounded text-[10px] font-bold shadow-sm">
                    <i class="fa-regular fa-calendar mr-1.5"></i> {{ \Carbon\Carbon::parse($desde)->format('d/m') }} - {{ \Carbon\Carbon::parse($hasta)->format('d/m') }}
                </span>
                
                @if($origen !== 'todos')
                    <span class="inline-flex items-center bg-indigo-50 text-indigo-600 border border-indigo-100 px-2 py-0.5 rounded text-[10px] font-bold shadow-sm">
                        <i class="fa-solid fa-mobile-screen mr-1.5"></i> {{ $origen == 'mios' ? 'Solo míos' : 'Solo admin' }}
                    </span>
                @endif
            @endif
            
            <span class="ml-auto text-[10px] font-black text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg border border-gray-200">
                {{ count($permisos) }} Res.
            </span>
        </div>

        <div class="space-y-4">
            @forelse($permisos as $permiso)
                @php
                    // Lógica de colores
                    if ($permiso->app_creacion == 1 || $permiso->estado_solicitud == 0) {
                        $colorBadge = 'bg-indigo-100 text-indigo-700 border-indigo-200';
                        $textoBadge = 'Asignado por admin';
                        $icon = '<i class="fa-solid fa-user-tie text-indigo-500"></i>';
                        $bgCard = 'bg-indigo-50/30';
                        $textoFecha = 'Asignado:';
                    } elseif ($permiso->estado_solicitud == 1) { 
                        $colorBadge = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                        $textoBadge = 'En revisión';
                        $icon = '<i class="fa-solid fa-clock-rotate-left text-yellow-500"></i>';
                        $bgCard = 'bg-white';
                        $textoFecha = 'Solicitado:';
                    } elseif ($permiso->estado_solicitud == 2) { 
                        $colorBadge = 'bg-green-100 text-green-700 border-green-200';
                        $textoBadge = 'Aprobado';
                        $icon = '<i class="fa-solid fa-check-circle text-green-500"></i>';
                        $bgCard = 'bg-green-50/30';
                        $textoFecha = 'Solicitado:';
                    } elseif ($permiso->estado_solicitud == 3) { 
                        $colorBadge = 'bg-red-100 text-red-700 border-red-200';
                        $textoBadge = 'Rechazado';
                        $icon = '<i class="fa-solid fa-circle-xmark text-red-400"></i>';
                        $bgCard = 'bg-red-50/30 opacity-75';
                        $textoFecha = 'Solicitado:';
                    } else { 
                        $colorBadge = 'bg-gray-100 text-gray-700 border-gray-200';
                        $textoBadge = $permiso->estado == 1 ? 'Activo' : 'Inactivo';
                        $icon = '<i class="fa-solid fa-info-circle text-gray-400"></i>';
                        $bgCard = 'bg-gray-50/30';
                        $textoFecha = 'Fecha:';
                    }
                @endphp

                <div x-data="{ openDeleteModal: false }" class="{{ $bgCard }} p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden transition-all">
                    
                    {{-- Etiqueta de estado y botón de borrar --}}
                    <div class="flex justify-between items-start mb-3">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">
                            {{ $textoFecha }} {{ $permiso->created_at->format('d/m/Y') }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-bold border {{ $colorBadge }} gap-1">
                                {!! $icon !!} {{ $textoBadge }}
                            </span>
                            
                            {{-- Botón eliminar (solo si está en revisión) --}}
                            @if($permiso->estado_solicitud == 1 && $permiso->app_creacion == 2)
                                <button @click="openDeleteModal = true" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-1.5 rounded-full transition-colors" title="Cancelar solicitud">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Tipo de permiso --}}
                    <h3 class="text-sm font-bold text-gray-800 leading-tight mb-1 flex items-center gap-2">
                        {{ $permiso->tipoPermiso->nombre ?? 'Permiso general' }}
                    </h3>

                    {{-- Motivo --}}
                    <p class="text-xs text-gray-600 italic mb-4 line-clamp-2">"{{ $permiso->motivo }}"</p>

                    {{-- Detalles dinámicos --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 grid grid-cols-2 gap-2 text-[10px]">
                        @if($permiso->fecha_inicio || $permiso->fecha_fin)
                            <div class="col-span-2">
                                <span class="text-gray-400 font-bold uppercase block mb-0.5">Vigencia:</span>
                                <span class="text-gray-700 font-bold">
                                    {{ $permiso->fecha_inicio ? ucfirst(\Carbon\Carbon::parse($permiso->fecha_inicio)->locale('es')->isoFormat('DD MMM, YYYY')) : '---' }} 
                                    @if($permiso->fecha_inicio !== $permiso->fecha_fin)
                                        al 
                                        {{ $permiso->fecha_fin ? ucfirst(\Carbon\Carbon::parse($permiso->fecha_fin)->locale('es')->isoFormat('DD MMM, YYYY')) : '---' }}
                                    @endif
                                </span>
                            </div>
                        @endif

                   
                        @if($permiso->hora_ini && $permiso->hora_fin)
                            <div class="col-span-2">
                                <span class="text-gray-400 font-bold uppercase block mb-0.5">Horario autorizado:</span>
                                <span class="text-gray-700 font-bold">
                                    <i class="fa-regular fa-clock mr-1"></i> 
                                    {{ \Carbon\Carbon::parse($permiso->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') }}
                                </span>
                            </div>
                        @endif

                        @if($permiso->valor)
                            <div>
                                <span class="text-gray-400 font-bold uppercase block mb-0.5">Tiempo:</span>
                                <span class="text-gray-700 font-bold"><i class="fa-regular fa-clock"></i> {{ $permiso->valor }} mins</span>
                            </div>
                        @endif

                        @if($permiso->tipoPermiso && $permiso->tipoPermiso->codigo === 'FUERA_RANGO')
                            <div>
                                <span class="text-gray-400 font-bold uppercase block mb-0.5">Rango GPS:</span>
                                <span class="text-gray-700 font-bold">
                                    <i class="fa-solid fa-location-dot"></i> {{ is_null($permiso->cantidad_mts) ? 'Libre' : $permiso->cantidad_mts . ' mts' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Modal de confirmación de eliminación --}}
                    <div x-show="openDeleteModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4 backdrop-blur-sm">
                        <div @click.away="openDeleteModal = false" class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl transform transition-all">
                            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-center text-gray-900 mb-2">¿Cancelar solicitud?</h3>
                            <p class="text-sm text-gray-500 text-center mb-6">
                                Estás a punto de cancelar tu solicitud de <strong class="text-gray-700">{{ $permiso->tipoPermiso->nombre }}</strong>. Esta acción no se puede deshacer.
                            </p>
                            <div class="flex gap-3">
                                <button @click="openDeleteModal = false" type="button" class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-200 transition-colors">
                                    Volver
                                </button>
                                <form action="{{ route('marcacion.permisos.destroy', $permiso->id) }}" method="POST" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-red-700 active:scale-95 transition-transform">
                                        Sí, cancelar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-3xl border border-gray-100 shadow-sm">
                    <div class="w-16 h-16 bg-blue-50 text-blue-300 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-inbox text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Sin resultados</h3>
                    <p class="text-xs text-gray-500 mt-1 px-6">No se encontraron permisos con los filtros seleccionados.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>