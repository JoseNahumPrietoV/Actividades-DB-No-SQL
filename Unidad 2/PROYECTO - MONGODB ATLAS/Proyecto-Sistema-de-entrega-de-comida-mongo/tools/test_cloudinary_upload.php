<?php
// tools/test_cloudinary_upload.php

require_once dirname(__DIR__) . '/Back-end/servicios/CloudinaryService.php';

use Snackver\Servicios\CloudinaryService;

echo "========================================================\n";
echo "PRUEBA DE SUBIDA A CLOUDINARY (Imágenes en la Nube)\n";
echo "========================================================\n\n";

// Crear una imagen de prueba temporal de 100x100 píxeles
$tempImage = tempnam(sys_get_temp_dir(), 'snackver_test_') . '.png';
$im = imagecreatetruecolor(100, 100);
$bgColor = imagecolorallocate($im, 230, 126, 34); // Color naranja Snackver
imagefill($im, 0, 0, $bgColor);
imagepng($im, $tempImage);
imagedestroy($im);

try {
    $res = CloudinaryService::subirImagen($tempImage, 'pruebas');
    if ($res['status'] === 'success') {
        echo "✅ ÉXITO: Imagen subida correctamente a la nube de Cloudinary!\n";
        echo "🌐 URL Segura HTTPS: " . $res['url'] . "\n";
        echo "🆔 Public ID: " . $res['public_id'] . "\n";
    } else {
        echo "⚠️ MODO LOCAL / ADVERTENCIA: " . $res['message'] . "\n";
        echo "📁 Ruta local generada: " . $res['url'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
} finally {
    if (file_exists($tempImage)) {
        unlink($tempImage);
    }
}

echo "\n========================================================\n";
