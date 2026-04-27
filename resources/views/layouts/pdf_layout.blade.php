<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reporte del sistema')</title>
    <style>
        /* Configuración general */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin-top: 120px; /* Aumentado para evitar solapamiento con el logo */
            margin-bottom: 60px; /* Margen inferior para el pie de página */
        }

        /* Encabezado fijo en todas las páginas */
        header {
            position: fixed;
            top: -60px; /* Posición fija superior */
            left: 0px;
            right: 0px;
            height: 95px; /* Ajustado para acomodar el logo sin cortar el texto */
            border-bottom: 1px solid #ddd;
        }

        /* Pie de página fijo en todas las páginas */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            border-top: 1px solid #ddd;
            line-height: 35px;
            color: #555;
        }

        /* Número de página (CSS para dompdf) */
        .page-number:before {
            content: "Página " counter(page);
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        
        /* Clases utilitarias */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    {{-- Encabezado --}}
    <header>
        @php
            $logoBase64 = null;
            if (isset($empresaGlobal) && $empresaGlobal->logo) {
                // Ruta absoluta del archivo en el disco del servidor
                $rutaAbsoluta = storage_path('app/public/' . $empresaGlobal->logo);

                if (file_exists($rutaAbsoluta)) {
                    // Conversión a base64 para incrustación directa en el PDF
                    $tipo = pathinfo($rutaAbsoluta, PATHINFO_EXTENSION);
                    $datos = file_get_contents($rutaAbsoluta);
                    $logoBase64 = 'data:image/' . $tipo . ';base64,' . base64_encode($datos);
                }
            }
        @endphp
        
        {{-- Contenedor del logo centrado --}}
        @if($logoBase64)
        <div style="text-align: center; width: 100%; padding-top: 30px;">
             {{-- Centrado horizontal y límite de altura --}}
             <img src="{{ $logoBase64 }}" alt="Logo de la empresa" style="max-height: 65px; width: auto;">
        </div>
        @endif

        {{-- Datos del reporte --}}
        <table style="border:none;">
            <tr>
                <td style="border:none; width: 20%;">
                    <h2 style="color: #d32f2f;">{{ $empresa->nombre ?? config('app.name') }}</h2>
                </td>
                <td style="border:none; text-align:center;">
                    <h1>Reloj marcador</h1>
                </td>
                <td style="border:none; width: 20%; text-align:right;">
                    <p>Fecha: {{ date('d/m/Y') }}</p>
                </td>
            </tr>
        </table>
    </header>

    {{-- Pie de página --}}
    <footer>
        <span class="page-number"></span> | Generado por: {{ auth()->user()->name ?? 'Sistema' }}
    </footer>

    {{-- Contenido dinámico del reporte --}}
    <main>
        @yield('content')
    </main>

</body>
</html>