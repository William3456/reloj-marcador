<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reporte del Sistema')</title>
    <style>
        /* Configuración general */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin-top: 100px; /* Margen superior para el encabezado */
            margin-bottom: 60px; /* Margen inferior para el pie de página */
        }

        /* Encabezado Fijo en todas las páginas */
        header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 80px;
            border-bottom: 1px solid #ddd;
        }

        /* Pie de página Fijo en todas las páginas */
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

        /* Número de página (CSS mágico para dompdf) */
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
        
        /* Clases utilitarias simples */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    {{-- Encabezado (Aparece en todas las hojas) --}}
    <header>
        <table style="border:none;">
            <tr>
                <td style="border:none; width: 20%;">
                    {{-- Aquí iría tu logo --}}
                    {{-- <img src="{{ public_path('img/logo.png') }}" width="100"> --}}
                    <h2 style="color: #d32f2f;">{{ $empresa->nombre }}</h2>
                </td>
                <td style="border:none; text-align:center;">
                    <h1>Reloj Marcador</h1>
                </td>
                <td style="border:none; width: 20%; text-align:right;">
                    <p>Fecha: {{ date('d/m/Y') }}</p>
                </td>
            </tr>
        </table>
    </header>

    {{-- Pie de página (Aparece en todas las hojas) --}}
    <footer>
        <span class="page-number"></span> | Generado por: {{ auth()->user()->name ?? 'Sistema' }}
    </footer>

    {{-- Contenido dinámico del reporte --}}
    <main>
        @yield('content')
    </main>

</body>
</html>