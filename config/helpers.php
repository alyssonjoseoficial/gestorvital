<?php
// helpers.php

// Função para sanitizar nomes de pastas, removendo acentos e caracteres especiais
function sanitize_folder_name($string) {
    // Converte a string para UTF-8, se já não estiver
    $string = mb_convert_encoding($string, 'UTF-8', 'auto');
    
    // Converte a string para um formato não acentuado
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    
    // Converte para minúsculo
    $string = strtolower($string);
    
    // Remove caracteres especiais, deixando apenas letras, números e espaços
    $string = preg_replace('/[^a-z0-9\s]/', '', $string);
    
    // Substitui múltiplos espaços por um único traço
    $string = preg_replace('/\s+/', '_', $string);
    
    return $string;
}

// Função para copiar uma pasta e seu conteúdo recursivamente
function recurse_copy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Função para converter uma cor hexadecimal (#RRGGBB) em uma string RGB
function hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

// Função para determinar a cor de contraste (preto ou branco)
function get_contrast_color($hexcolor) {
    // Remove o '#' se ele existir
    $hexcolor = ltrim($hexcolor, '#');
    // Converte a cor hexadecimal para RGB
    $r = hexdec(substr($hexcolor, 0, 2));
    $g = hexdec(substr($hexcolor, 2, 2));
    $b = hexdec(substr($hexcolor, 4, 2));
    // Calcula a luminosidade (luminance)
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    // Retorna branco para cores escuras e preto para cores claras
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}
?>