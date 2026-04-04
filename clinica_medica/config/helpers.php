<?php

/**
 * Retorna a cor de contraste (preto ou branco) para uma cor de fundo hexadecimal.
 * Usa o cálculo de luminância para determinar o brilho da cor.
 * @param string $hex_color A cor em formato hexadecimal (ex: #ffffff).
 * @return string A cor de contraste, #ffffff para cores escuras e #000000 para cores claras.
 */
function get_contrast_color($hex_color) {
    // Remove o '#' se houver
    $hex_color = ltrim($hex_color, '#');
    // Se a cor for curta (ex: #fff), a expande para o formato completo (ex: #ffffff)
    if (strlen($hex_color) == 3) {
        $r = hexdec(substr($hex_color, 0, 1) . substr($hex_color, 0, 1));
        $g = hexdec(substr($hex_color, 1, 1) . substr($hex_color, 1, 1));
        $b = hexdec(substr($hex_color, 2, 1) . substr($hex_color, 2, 1));
    } else {
        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));
    }

    // Calcula a luminância (brilho) da cor
    // A fórmula é baseada na percepção humana de brilho
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    // Se a luminância for menor que 0.5, a cor é escura, use branco para o texto.
    // Caso contrário, a cor é clara, use preto.
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

?>