<?php
/**
 * Script para generar iconos PWA
 * Ejecutar: php generate-icons.php
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/public/icons';

// Colores
$bgColor = [15, 23, 42]; // #0f172a
$textColor = [20, 184, 166]; // #14b8a6

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Habilitar alpha blending
    imagealphablending($image, true);
    imagesavealpha($image, true);
    
    // Color de fondo
    $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    imagefill($image, 0, 0, $bg);
    
    // Color del texto
    $text = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    
    // Dibujar la "S"
    $fontSize = $size * 0.6;
    $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    
    // Si no existe la fuente, usar fuente integrada
    if (file_exists($fontFile)) {
        $bbox = imagettfbbox($fontSize, 0, $fontFile, 'S');
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = ($size - $textWidth) / 2 - $bbox[0];
        $y = ($size + $textHeight) / 2 - $bbox[1] - ($textHeight * 0.1);
        imagettftext($image, $fontSize, 0, (int)$x, (int)$y, $text, $fontFile, 'S');
    } else {
        // Fallback: usar fuente integrada
        $font = 5; // Fuente más grande disponible
        $textWidth = imagefontwidth($font);
        $textHeight = imagefontheight($font);
        $x = ($size - $textWidth) / 2;
        $y = ($size - $textHeight) / 2;
        imagestring($image, $font, (int)$x, (int)$y, 'S', $text);
    }
    
    // Guardar imagen
    $filename = "{$outputDir}/icon-{$size}x{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "Generated: icon-{$size}x{$size}.png\n";
}

echo "\nAll icons generated successfully!\n";
