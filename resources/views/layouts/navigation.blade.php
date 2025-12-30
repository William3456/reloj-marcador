@php
    $role = Auth::user()->id_rol;
    $activeBtnClass = "text-blue-700 bg-blue-50";
    $inactiveBtnClass = "text-gray-600 hover:bg-blue-50 hover:text-blue-700";
    $activeLinkClass = "font-semibold text-blue-700";
    $inactiveLinkClass = "text-gray-600 hover:text-blue-700";
@endphp

<aside 
    class="fixed inset-y-0 left-0 z-40 bg-white border-r border-gray-200 flex flex-col transform xl:static xl:translate-x-0 shadow-lg xl:shadow-none transition-all duration-300"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen,
        'w-64': sidebarExpanded, 
        'w-20': !sidebarExpanded,
        'transition-all duration-300': sidebarReady
    }">
    
    <div class="flex items-center h-16 border-b border-gray-200 shrink-0 transition-all duration-300"
         :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center'">
        
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 overflow-hidden whitespace-nowrap">
            <x-application-logo class="block h-8 w-auto fill-current text-blue-600 shrink-0" />
            
            <span x-show="sidebarExpanded" 
                  x-cloak
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 transform scale-90"
                  x-transition:enter-end="opacity-100 transform scale-100"
                  class="text-lg font-bold text-gray-800 tracking-wider uppercase ml-2">
                {{ config('app.name') }}
            </span>
        </a>

        <button @click="sidebarExpanded = !sidebarExpanded" 
                class="hidden xl:block text-gray-500 hover:text-blue-600 focus:outline-none bg-gray-100 p-1 rounded-md transition-colors"
                :class="!sidebarExpanded ? 'absolute -right-3 top-6 shadow-md border border-gray-200' : ''">
            <i class="fas" :class="sidebarExpanded ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
        </button>
    </div>

    <div class="border-b border-gray-200 bg-gray-50 shrink-0 transition-all duration-300"
         :class="sidebarExpanded ? 'px-6 py-4' : 'p-2 py-4 flex flex-col items-center'">
        
        <p x-show="sidebarExpanded" x-cloak class="text-xs text-gray-500 uppercase tracking-wider mb-1">Hola,</p>
        
        <a href="{{ route('profile.edit') }}" class="group block cursor-pointer overflow-hidden">
            <div class="flex items-center" :class="sidebarExpanded ? 'justify-between' : 'justify-center'">
                <span x-show="sidebarExpanded" x-cloak class="font-semibold text-gray-800 truncate group-hover:text-blue-700 transition-colors" title="{{ Auth::user()->name }}">
                    {{ implode(' ', array_slice(explode(' ', Auth::user()->name), 0, 2)) }}
                </span>
                
                <i class="fas fa-user-cog text-gray-400 group-hover:text-blue-700 transition-colors text-sm"
                   :class="sidebarExpanded ? '' : 'text-lg'"></i>
            </div>
            
            <p x-show="sidebarExpanded" x-cloak class="text-xs text-gray-500 font-medium mt-0.5 truncate">
                {{ optional(Auth::user()->rol)->rol_name ?? 'Sin Rol' }}
            </p>
        </a>
    </div>

    <nav class="flex-1 py-4 space-y-2 custom-scrollbar"
         :class="sidebarExpanded ? 'px-4 overflow-y-auto overflow-x-hidden' : 'px-2 overflow-visible'">
        
        <a href="{{ route('dashboard') }}" 
           class="flex items-center py-3 rounded-lg transition-all duration-200 group relative
           {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md' : $inactiveBtnClass }}"
           :class="sidebarExpanded ? 'px-4' : 'justify-center px-2'">
            <i class="fas fa-home w-5 text-center shrink-0"></i>
            <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap transition-opacity duration-200">Inicio</span>
            <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                Inicio
            </div>
        </a>

        @if ($role == 1 || $role == 2)
        
            <div x-data="{ open: {{ request()->routeIs('horarios.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('horarios.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-clock w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Horarios</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    
                    <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Horarios
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('horarios.index') }}" class="block py-2 text-sm {{ request()->routeIs('horarios.index') ? $activeLinkClass : $inactiveLinkClass }}">Listado</a>
                    <a href="{{ route('horarios.create') }}" class="block py-2 text-sm {{ request()->routeIs('horarios.create') ? $activeLinkClass : $inactiveLinkClass }}">Crear</a>
                </div>
            </div>
            
            <div x-data="{ open: {{ request()->routeIs('sucursales.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('sucursales.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-store w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Sucursales</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                     <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Sucursales
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('sucursales.index') }}" class="block py-2 text-sm {{ request()->routeIs('sucursales.index') ? $activeLinkClass : $inactiveLinkClass }}">Lista sucursales</a>
                    <a href="{{ route('sucursales.create') }}" class="block py-2 text-sm {{ request()->routeIs('sucursales.create') ? $activeLinkClass : $inactiveLinkClass }}">Añadir sucursal</a>
                </div>
            </div>

            <div x-data="{ open: {{ request()->routeIs('empleados.*') || request()->routeIs('empleadoshorarios.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('empleados.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-users w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Empleados</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                    <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Empleados
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('empleados.index') }}" class="block py-2 text-sm {{ request()->routeIs('empleados.index') ? $activeLinkClass : $inactiveLinkClass }}">Listado</a>
                    <a href="{{ route('empleados.create') }}" class="block py-2 text-sm {{ request()->routeIs('empleados.create') ? $activeLinkClass : $inactiveLinkClass }}">Crear empleado</a>
                    <a href="{{ route('empleadoshorarios.asign') }}" class="block py-2 text-sm {{ request()->routeIs('empleadoshorarios.asign') ? $activeLinkClass : $inactiveLinkClass }}">Asignar horarios</a>
                </div>
            </div>

            <div x-data="{ open: {{ request()->routeIs('permisos.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('permisos.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-user-shield w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Permisos</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                     <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Permisos
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('permisos.index') }}" class="block py-2 text-sm {{ request()->routeIs('permisos.index') ? $activeLinkClass : $inactiveLinkClass }}">Ver permisos</a>
                    <a href="{{ route('permisos.create') }}" class="block py-2 text-sm {{ request()->routeIs('permisos.create') ? $activeLinkClass : $inactiveLinkClass }}">Crear permisos</a>
                </div>
            </div>

            <div x-data="{ open: {{ request()->routeIs('departamentos.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('departamentos.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-building w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Departamentos</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                     <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Departamentos
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('departamentos.index') }}" class="block py-2 text-sm {{ request()->routeIs('departamentos.index') ? $activeLinkClass : $inactiveLinkClass }}">Ver departamentos</a>
                    <a href="{{ route('departamentos.create') }}" class="block py-2 text-sm {{ request()->routeIs('departamentos.create') ? $activeLinkClass : $inactiveLinkClass }}">Crear departamento</a>
                </div>
            </div>

            <div x-data="{ open: {{ request()->routeIs('puestos.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('puestos.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Puestos</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                    <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Puestos
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('puestos.index') }}" class="block py-2 text-sm {{ request()->routeIs('puestos.index') ? $activeLinkClass : $inactiveLinkClass }}">Ver puestos</a>
                    <a href="{{ route('puestos.create') }}" class="block py-2 text-sm {{ request()->routeIs('puestos.create') ? $activeLinkClass : $inactiveLinkClass }}">Crear puesto</a>
                </div>
            </div>
            <div x-data="{ open: {{ request()->routeIs('reportes.*') ? 'true' : 'false' }} }">
                <button @click="if(!sidebarExpanded) { sidebarExpanded = true; open = true; } else { open = !open; }"
                        class="w-full flex items-center py-3 rounded-lg transition-all group relative
                        {{ request()->routeIs('reportes.*') ? $activeBtnClass : $inactiveBtnClass }}"
                        :class="sidebarExpanded ? 'justify-between px-4' : 'justify-center px-2'">
                    <div class="flex items-center">
                        <i class="fa-solid fa-file w-5 text-center shrink-0"></i>
                        <span x-show="sidebarExpanded" x-cloak class="ml-3 font-medium whitespace-nowrap">Reportes</span>
                    </div>
                    <i x-show="sidebarExpanded" x-cloak class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>

                    <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                        Reportes
                    </div>
                </button>
                <div x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-9 space-y-1 border-l-2 border-gray-200 pl-2">
                    <a href="{{ route('reportes.empleados.empleados_rep') }}" class="block py-2 text-sm {{ request()->routeIs('reportes.empleados.sucursal') ? $activeLinkClass : $inactiveLinkClass }}">Empleados</a>
                    
                </div>
            </div>
        @endif
    </nav>

    <div class="border-t border-gray-200 bg-gray-50 shrink-0 transition-all duration-300"
         :class="sidebarExpanded ? 'p-4' : 'p-2'">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                class="flex items-center w-full text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-all group relative"
                :class="sidebarExpanded ? 'px-4 py-2' : 'justify-center py-3'">
                
                <i class="fas fa-sign-out-alt w-5 text-center shrink-0"></i>
                <span x-show="sidebarExpanded" x-cloak class="ml-3 whitespace-nowrap">Cerrar Sesión</span>

                <div x-show="!sidebarExpanded" x-cloak class="absolute left-full ml-2 bg-red-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none shadow-lg">
                    Salir
                </div>
            </button>
        </form>
    </div>

</aside>

<div x-show="sidebarOpen" 
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-black bg-opacity-50 z-30 xl:hidden"
     style="display: none;">
</div>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a0aec0; }
</style>