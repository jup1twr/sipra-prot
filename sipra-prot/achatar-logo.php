<?php

$origem = __DIR__.'/public/img/sipra-logo-completo.png';

$img = imagecreatefrompng($origem);
$largura = imagesx($img);
$altura = imagesy($img);

// Normaliza o PNG e mantém a transparência
imagepalettetotruecolor($img);
imagealphablending($img, false);
imagesavealpha($img, true);

// Limpa os pontinhos semitransparentes (ruído) ao redor do desenho
$vazio = imagecolorallocatealpha($img, 255, 255, 255, 127);
for ($y = 0; $y < $altura; $y++) {
    for ($x = 0; $x < $largura; $x++) {
        $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
        if ($alpha > 100) {
            imagesetpixel($img, $x, $y, $vazio);
        }
    }
}

// Versão 1: transparente e limpa
imagepng($img, __DIR__.'/public/img/sipra-logo-completo-pdf.png');

// Versão 2 (reserva): fundo branco, que no PDF branco fica igual
$branco = imagecreatetruecolor($largura, $altura);
imagefill($branco, 0, 0, imagecolorallocate($branco, 255, 255, 255));
imagealphablending($branco, true);
imagecopy($branco, $img, 0, 0, 0, 0, $largura, $altura);
imagepng($branco, __DIR__.'/public/img/sipra-logo-completo-pdf-branco.png');

echo "Pronto: {$largura} x {$altura}\n";