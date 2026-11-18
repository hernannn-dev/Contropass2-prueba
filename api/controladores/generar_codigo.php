<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

$cedula = isset($_GET['cedula']) ? preg_replace('/[^0-9]/', '', $_GET['cedula']) : '';
$descargar = isset($_GET['descargar']) && $_GET['descargar'] == '1';

if (empty($cedula)) {
    exit("Documento no válido");
}

// Configuración de escalas según el requerimiento (Visualización vs Impresión)
$es_grande = (isset($_GET['tamanio']) && $_GET['tamanio'] == 'grande') || $descargar;
$tamanio = $es_grande ? 4 : 2;         // Grosor de barras optimizado
$alto_barras = $es_grande ? 140 : 60;  // Altura del código de barras

$generator = new BarcodeGeneratorPNG();
$barcodeImage = $generator->getBarcode($cedula, $generator::TYPE_CODE_128, $tamanio, $alto_barras);

// Procesamiento mediante GD para integrar el número centrado abajo
$imageResource = imagecreatefromstring($barcodeImage);
$width = imagesx($imageResource);
$height = imagesy($imageResource);

// Espacio reservado para el texto inferior
$espacioTexto = $es_grande ? 45 : 20;
$newHeight = $height + $espacioTexto;

$newImage = imagecreatetruecolor($width, $newHeight);

$white = imagecolorallocate($newImage, 255, 255, 255);
$black = imagecolorallocate($newImage, 15, 15, 15);

imagefilledrectangle($newImage, 0, 0, $width, $newHeight, $white);
imagecopy($newImage, $imageResource, 0, 0, 0, 0, $width, $height);

// Renderizado del texto de la cédula centrado
$font = $es_grande ? 5 : 3;
$charWidth = $es_grande ? 12 : 6;
$textWidth = $charWidth * strlen($cedula);
$textX = (int)(($width - $textWidth) / 2);
$textY = $height + ($es_grande ? 12 : 4);

imagestring($newImage, $font, $textX, $textY, $cedula, $black);

// Si es para descarga, aplicamos resampling para maximizar la nitidez en papel
if ($es_grande) {
    $scaleFactor = 2;
    $finalWidth = $width * $scaleFactor;
    $finalHeight = $newHeight * $scaleFactor;
    
    $finalImage = imagecreatetruecolor($finalWidth, $finalHeight);
    imagefilledrectangle($finalImage, 0, 0, $finalWidth, $finalHeight, $white);
    imagecopyresampled($finalImage, $newImage, 0, 0, 0, 0, $finalWidth, $finalHeight, $width, $newHeight);
    
    imagedestroy($newImage);
    $newImage = $finalImage;
}

header("Content-Type: image/png");

if ($descargar) {
    header("Content-Disposition: attachment; filename=\"Carnet_ControlPass_{$cedula}.png\"");
}

imagepng($newImage);
imagedestroy($imageResource);
imagedestroy($newImage);
exit();
?>