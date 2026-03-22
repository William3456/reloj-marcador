<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licencia de pruebas expirada - Reloj Marcador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4 font-sans">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden text-center relative">
        {{-- Patrón de fondo superior --}}
        <div class="h-32 bg-red-600 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20">
                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="diagonal-stripes" width="20" height="20" patternTransform="rotate(45)">
                            <rect width="10" height="20" fill="#ffffff"></rect>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#diagonal-stripes)"></rect>
                </svg>
            </div>
        </div>

        {{-- Icono central superpuesto --}}
        <div class="relative -mt-12 flex justify-center">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-lg p-2 border-4 border-gray-100">
                <div class="w-full h-full bg-red-50 rounded-full flex items-center justify-center text-red-600 text-4xl">
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

        <div class="p-8 pt-6">
            <h2 class="text-2xl font-black text-gray-800 mb-2">Período de prueba finalizado</h2>
            <p class="text-gray-500 text-sm mb-6">
                El acceso al sistema para <span class="font-bold text-gray-700">{{ $empresa->nombre ?? 'su empresa' }}</span> ha sido suspendido temporalmente.
            </p>

            <div class="bg-red-50 border border-red-100 rounded-xl p-4 mb-8 text-left">
                <div class="flex items-start">
                    <i class="fa-solid fa-circle-info text-red-500 mt-1 mr-3"></i>
                    <div>
                        <h4 class="text-sm font-bold text-red-800">¿Qué sucedió?</h4>
                        <p class="text-xs text-red-600 mt-1">El tiempo asignado para la demostración del sistema ha concluido. Para restaurar el acceso de todos los empleados y conservar sus registros de asistencia, es necesario adquirir la licencia permanente.</p>
                    </div>
                </div>
            </div>

            <a href="mailto:soporte@tecnologiassv.org" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition-all">
                <i class="fa-solid fa-headset mr-2"></i> Contactar a Soporte
            </a>

            @if(Auth::check())
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-gray-400 hover:text-gray-600 underline">
                        Cerrar sesión actual
                    </button>
                </form>
            @endif
        </div>
    </div>

</body>
</html>