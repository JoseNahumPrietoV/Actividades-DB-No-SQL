<?php
// tools/test_cache_benchmark.php
$endpoints = [
    'CSS Panel Cliente' => 'http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/Back-end/css/panel-cliente.css?v=2.3',
    'JS Notificaciones' => 'http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/Back-end/js/notificaciones.js?v=2.3',
    'Categorías JSON'   => 'http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/Front-end/obtener_categorias.php',
    'Página Principal'  => 'http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/index.php'
];

echo "========================================================\n";
echo "    BENCHMARK DE RENDIMIENTO Y CABECERAS DE CACHÉ       \n";
echo "========================================================\n\n";

foreach ($endpoints as $nombre => $url) {
    // Primera petición (cold)
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $tiempo1 = round($info['total_time'] * 1000, 2);
    $status = $info['http_code'];

    // Segunda petición (warm/cache)
    $ch2 = curl_init($url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HEADER, false);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
    $resp2 = curl_exec($ch2);
    $info2 = curl_getinfo($ch2);
    curl_close($ch2);

    $tiempo2 = round($info2['total_time'] * 1000, 2);

    echo "[$nombre]\n";
    echo "  • URL: $url\n";
    echo "  • HTTP Status: $status\n";
    echo "  • Tiempo Petición 1: {$tiempo1} ms\n";
    echo "  • Tiempo Petición 2: {$tiempo2} ms\n";

    $headerSize = $info['header_size'];
    $headers = substr($resp, 0, $headerSize);
    foreach (explode("\r\n", $headers) as $h) {
        if (stripos($h, 'cache-control') !== false || stripos($h, 'expires') !== false) {
            echo "  • Header: " . trim($h) . "\n";
        }
    }
    echo "\n";
}
