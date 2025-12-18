@props(['title' => config('app.name')])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">


<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    @stack('styles')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    {{-- 1. Incluir jQuery para la petición AJAX --}}
    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- AlertifyJS -->
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>

    <!-- CSS base -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css" />

    <!-- Tema (elegí solo uno, por ejemplo **default**) -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/default.min.css" />
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Leaflet buscador -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch/dist/geosearch.umd.js"></script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAZD_pQk0VjDhq6Q8elxekbfLFvUOZM2Jg&libraries=places"></script>

    <link rel="stylesheet" href="{{ asset('css/datatables.css') }}">

    <!-- Iconos -->
    <script src="https://kit.fontawesome.com/b52b18aa29.js" crossorigin="anonymous"></script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-200">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-gray-100 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')

</body>

</html>