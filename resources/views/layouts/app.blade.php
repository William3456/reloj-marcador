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

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/b52b18aa29.js" crossorigin="anonymous"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/default.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch/dist/geosearch.umd.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAZD_pQk0VjDhq6Q8elxekbfLFvUOZM2Jg&libraries=places"></script>
    <link rel="stylesheet" href="{{ asset('css/datatables.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-200">
    <div x-data="{ 
            sidebarOpen: false, 
            sidebarExpanded: JSON.parse(localStorage.getItem('sidebarExpanded') || 'true'),
            sidebarReady: false 
        }" 
        x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebarExpanded', value)); 
                setTimeout(() => sidebarReady = true, 300)" 
        class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            
            <div class="bg-white border-b border-gray-200 h-16 flex items-center px-4 shadow-sm xl:hidden shrink-0 z-30">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none p-2 rounded-md hover:bg-gray-100">
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
    @stack('scripts')
</body>
</html>