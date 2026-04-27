@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/b52b18aa29.js" crossorigin="anonymous"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/default.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch/dist/geosearch.umd.js"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAZD_pQk0VjDhq6Q8elxekbfLFvUOZM2Jg&libraries=places"></script>
    <link rel="stylesheet" href="{{ asset('css/datatables.css') }}">
    @if(isset($empresaGlobal) && $empresaGlobal->favicon)
        <link rel="icon" href="{{ Storage::url($empresaGlobal->favicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    @endif
    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-200">
    <div x-data="{ 
            sidebarOpen: false, 
            sidebarExpanded: JSON.parse(localStorage.getItem('sidebarExpanded') || 'true'),
            sidebarReady: false 
        }" x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebarExpanded', value)); 
                setTimeout(() => sidebarReady = true, 300)" class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="flex-1 flex flex-col h-full overflow-hidden relative">

            <div
                class="bg-white border-b border-gray-200 h-16 flex items-center px-4 shadow-sm xl:hidden shrink-0 z-30">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-500 hover:text-gray-700 focus:outline-none p-2 rounded-md hover:bg-gray-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <span class="ml-4 font-bold text-gray-700 truncate">{{ $title ?? config('app.name') }}</span>
            </div>

            @isset($header)
                <header class="bg-gray-100 shadow-sm border-b border-gray-300 z-20">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1 overflow-y-auto custom-scrollbar">
                {{ $slot }}
            </main>
        </div>
    </div>
    {{-- ========================================================= --}}
    {{-- Marca de agua flotante: modo demo --}}
    {{-- ========================================================= --}}
    @if(isset($empresaGlobal) && $empresaGlobal->tipo_licencia == 0)
        @php
            // Aseguramos comparar solo las fechas (00:00:00) para tener un número entero exacto
            $hoy = \Carbon\Carbon::today();
            $vencimiento = $empresaGlobal->fecha_exp_licencia ? \Carbon\Carbon::parse($empresaGlobal->fecha_exp_licencia)->startOfDay() : null;
            $diasRestantes = $vencimiento ? (int) $hoy->diffInDays($vencimiento, false) : 0;

            // Lógica de colores y textos
            if ($diasRestantes > 3) {
                $bgGradient = 'from-blue-600 to-indigo-600';
                $textoTiempo = $diasRestantes . ' días restantes';
                $iconoAnimacion = 'animate-spin-slow';
            } elseif ($diasRestantes > 0) {
                $bgGradient = 'from-orange-500 to-red-500';
                $textoTiempo = 'Solo ' . $diasRestantes . ' días restantes';
                $iconoAnimacion = 'animate-pulse';
            } elseif ($diasRestantes === 0) {
                // Hoy es el último día, aún está activa
                $bgGradient = 'from-red-600 to-red-800 ring-4 ring-red-500/50';
                $textoTiempo = '¡Último día de prueba!';
                $iconoAnimacion = 'animate-bounce';
            } else {
                $bgGradient = 'from-gray-700 to-gray-900';
                $textoTiempo = 'Prueba finalizada';
                $iconoAnimacion = '';
            }
        @endphp

        <div class="fixed bottom-6 right-6 z-[9999] pointer-events-none">
            <div
                class="bg-gradient-to-r {{ $bgGradient }} text-white px-5 py-2.5 rounded-full shadow-2xl border border-white/20 flex items-center gap-3 backdrop-blur-md opacity-95 transform transition-all duration-300">
                <div class="bg-white/20 rounded-full w-6 h-6 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-stopwatch {{ $iconoAnimacion }} text-xs"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-black tracking-widest text-[10px] uppercase leading-none">Modo demo</span>
                    <span class="text-[11px] font-medium leading-tight text-white/90">{{ $textoTiempo }}</span>
                </div>
            </div>
        </div>
    @endif
    @stack('scripts')
</body>

</html>