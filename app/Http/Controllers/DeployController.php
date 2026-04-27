<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DeployController extends Controller
{
    public function procesarActualizacion()
    {
        // 1. Rutas Base
        $baseAppPath = base_path(); // /home/tecno2/repositories/reloj-marcador
        $publicHtmlPath = '/home/tecno2/public_html';
        
        $zipPath = $baseAppPath . '/update.zip';
        $tempPath = storage_path('app/temp_extract_' . time());
        $timestamp = date('Ymd_His');

        if (!File::exists($zipPath)) {
            return response()->json([
                'status' => 'error',
                'mensaje' => "No se encontró el update.zip en la raíz",
                'ruta_buscada' => $zipPath
            ], 404);
        }

        // 2. Extraer el ZIP
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            File::makeDirectory($tempPath, 0755, true, true);
            $zip->extractTo($tempPath);
            $zip->close();
        } else {
            return response()->json([
                'status' => 'error',
                'mensaje' => "Error al abrir el archivo ZIP."
            ], 500);
        }

        $archivosNuevos = File::allFiles($tempPath);
        
        // Estructura detallada para el JSON de respuesta
        $detalleReporte = [
            'ignorados' => [],
            'procesados' => [],
            'errores' => []
        ];

        // 3. Procesar cada archivo
        foreach ($archivosNuevos as $archivo) {
            $rutaRelativa = $archivo->getRelativePathname(); // Ej: app/Http/Controllers/Test.php

            // REGLA A: Ignorar carpeta public/build/
            if (Str::startsWith($rutaRelativa, 'public/build/')) {
                $detalleReporte['ignorados'][] = [
                    'archivo' => $rutaRelativa,
                    'motivo'  => 'Regla de exclusión activa para public/build/'
                ];
                continue;
            }

            // REGLA B: Definir Destinos
            $destinos = [
                $baseAppPath . '/' . $rutaRelativa
            ];

            if (Str::startsWith($rutaRelativa, 'public/js/')) {
                $destinos[] = $publicHtmlPath . '/' . $rutaRelativa;
            }

            // 4. Ejecutar Backup y Actualización
            foreach ($destinos as $rutaDestinoFinal) {
                
                $rutaLimpiaDestino = str_replace('/home/tecno2/', '', $rutaDestinoFinal);
                $rutaLimpiaBackup = null;
                $huboBackup = false;

                try {
                    // BACKUP
                    if (File::exists($rutaDestinoFinal)) {
                        $info = pathinfo($rutaDestinoFinal);
                        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
                        $rutaBackup = $info['dirname'] . '/' . $info['filename'] . '_bk_' . $timestamp . $extension;
                        
                        File::copy($rutaDestinoFinal, $rutaBackup);
                        
                        $rutaLimpiaBackup = str_replace('/home/tecno2/', '', $rutaBackup);
                        $huboBackup = true;
                    }

                    // DESPLIEGUE
                    File::makeDirectory(dirname($rutaDestinoFinal), 0755, true, true);
                    File::copy($archivo->getRealPath(), $rutaDestinoFinal);
                    
                    // Registrar éxito
                    $detalleReporte['procesados'][] = [
                        'origen_zip'    => $rutaRelativa,
                        'destino_final' => $rutaLimpiaDestino,
                        'se_creo_bk'    => $huboBackup,
                        'ruta_bk'       => $rutaLimpiaBackup
                    ];

                } catch (\Exception $e) {
                    // Registrar si falla al copiar algún archivo (ej. problemas de permisos)
                    $detalleReporte['errores'][] = [
                        'archivo' => $rutaRelativa,
                        'destino' => $rutaLimpiaDestino,
                        'error'   => $e->getMessage()
                    ];
                }
            }
        }

        // 5. Limpiar temporales
        File::deleteDirectory($tempPath);
        File::delete($zipPath);

        // 6. Respuesta JSON detallada
        return response()->json([
            'status'  => empty($detalleReporte['errores']) ? 'success' : 'warning',
            'mensaje' => 'Proceso de despliegue finalizado',
            'resumen' => [
                'total_archivos_en_zip' => count($archivosNuevos),
                'archivos_procesados'   => count($detalleReporte['procesados']),
                'archivos_ignorados'    => count($detalleReporte['ignorados']),
                'errores'               => count($detalleReporte['errores'])
            ],
            'detalle' => $detalleReporte
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}