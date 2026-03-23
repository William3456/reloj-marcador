<x-app-layout title="Gestión de licencias">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                {{ __('Gestión de licencias ') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        
        
        @if (session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-xl shadow-sm">
                <p class="text-green-700 font-bold"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</p>
            </div>
        @endif

        {{-- Grid de Empresas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            
            @foreach($empresas as $empresa)
                @php

                    $esEmpresaActual = $empresa->dominio === $currentHost;

                    $hoy = \Carbon\Carbon::today();
                    $vencimiento = $empresa->fecha_exp_licencia ? \Carbon\Carbon::parse($empresa->fecha_exp_licencia)->startOfDay() : null;
                    
                    $esPro = $empresa->tipo_licencia == 1;
                    $esDemo = $empresa->tipo_licencia == 0;
                    
                    // Aseguramos que sea un entero (0 significa que vence hoy)
                    $diasRestantes = $vencimiento ? (int) $hoy->diffInDays($vencimiento, false) : 0;
                    $estaVencida = $esDemo && $diasRestantes < 0;
                    
                    // Si vence hoy ($diasRestantes === 0), el borde será naranja, si no, azul o rojo.
                    $colorBorde = $esPro ? 'border-yellow-400' : ($estaVencida ? 'border-red-500' : ($diasRestantes === 0 ? 'border-orange-500' : 'border-blue-500'));
                    $colorFondoIcono = $esPro ? 'bg-yellow-100 text-yellow-600' : ($estaVencida ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600');
                @endphp

                {{-- Tarjeta de la Empresa (Con efecto especial si es la actual) --}}
                <div class="relative bg-white rounded-2xl border-t-4 {{ $colorBorde }} flex flex-col overflow-hidden transition-all duration-300
                     {{ $esEmpresaActual ? ' shadow-2xl shadow-indigo-200/50 scale-[1.02] z-10' : 'shadow-sm hover:shadow-md' }}" 
                     x-data="{ tipoLicencia: '{{ $empresa->tipo_licencia }}' }">
                    
                    {{-- 🌟 BADGE FLOTANTE PARA LA SESIÓN ACTUAL --}}
                    @if($esEmpresaActual)
                        <div class="absolute top-0 right-0 bg-indigo-500 text-white text-[9px] font-black px-3 py-1.5 rounded-bl-xl uppercase tracking-widest shadow-sm flex items-center">
                            <i class="fa-solid fa-location-dot mr-1.5 animate-bounce"></i> Sesión Actual
                        </div>
                    @endif

                    {{-- Cabecera de la Tarjeta --}}
                    <div class="p-5 border-b border-gray-100 flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $colorFondoIcono }}">
                            <i class="fa-solid {{ $esPro ? 'fa-crown' : ($estaVencida ? 'fa-lock' : 'fa-stopwatch') }} text-xl"></i>
                        </div>
                        <div class="flex-grow pt-1">
                            <h3 class="text-lg font-black text-gray-800 leading-tight pr-16">{{ $empresa->nombre }}</h3>
                            <p class="text-xs {{ $esEmpresaActual ? 'text-indigo-500 font-bold' : 'text-gray-400' }} mt-1">
                                <i class="fa-solid fa-globe mr-1"></i> {{ $empresa->dominio }}
                            </p>
                            
                            {{-- Badge de Estado --}}
                            <div class="mt-3">
                                @if($esPro)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                        LICENCIA PRO
                                    </span>
                                @elseif($diasRestantes < 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        VENCIDA HACE {{ abs($diasRestantes) }} DÍAS
                                    </span>
                                @elseif($diasRestantes === 0)
                                    {{-- 🌟 CORRECCIÓN: HOY --}}
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200 animate-pulse">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> ¡ÚLTIMO DÍA DE PRUEBA!
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        DEMO: {{ $diasRestantes }} DÍAS RESTANTES
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Formulario de Actualización --}}
                    <form action="{{ route('empresas.update_licencia', $empresa->id) }}" method="POST" class="p-5 flex flex-col flex-grow {{ $esEmpresaActual ? 'bg-indigo-50/30' : 'bg-gray-50/30' }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4 flex-grow">
                            {{-- Select Tipo Licencia --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipo de Licencia</label>
                                <select name="tipo_licencia" x-model="tipoLicencia" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                                    <option value="0">Prueba (Demo)</option>
                                    <option value="1">Permanente (PRO)</option>
                                </select>
                            </div>

                            {{-- Input Fecha --}}
                            <div x-show="tipoLicencia == '0'" x-cloak x-transition>
                                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Vence el:</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fa-regular fa-calendar text-sm"></i>
                                    </div>
                                    <input type="date" name="fecha_exp_licencia" 
                                        value="{{ $empresa->fecha_exp_licencia ? \Carbon\Carbon::parse($empresa->fecha_exp_licencia)->format('Y-m-d') : '' }}"
                                        :required="tipoLicencia == '0'"
                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                        class="w-full pl-9 text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                                </div>
                            </div>
                        </div>

                        {{-- Botón Guardar --}}
                        <div class="mt-5 pt-4 border-t {{ $esEmpresaActual ? 'border-indigo-100' : 'border-gray-100' }}">
                            <button type="submit" class="w-full bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-400 font-bold py-2.5 px-4 rounded-lg shadow-sm transition-all text-sm flex items-center justify-center group active:scale-95">
                                <i class="fa-solid fa-save mr-2 text-blue-400 group-hover:text-blue-600 transition-colors"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>