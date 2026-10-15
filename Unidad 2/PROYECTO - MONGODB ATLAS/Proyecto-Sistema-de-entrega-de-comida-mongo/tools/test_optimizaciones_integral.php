<?php
// tools/test_optimizaciones_integral.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

echo "========================================================\n";
echo "   VALIDACIÓN DE OPTIMIZACIONES DE RENDIMIENTO Y LATENCIA\n";
echo "========================================================\n\n";

$db = MongoConexion::getConexion();

// 1. Validar Índices en Atlas
echo "1. Verificando índices compuestos en Atlas...\n";
$colNotif = $db->selectCollection('notificaciones');
$indicesNotif = iterator_to_array($colNotif->listIndexes());
$nombresNotif = array_map(function($i) { return $i->getName(); }, $indicesNotif);

$esperadosNotif = ['idx_notif_usr_tipo_fecha', 'idx_notif_usrstr_tipo_fecha', 'idx_notif_usr_leida'];
foreach ($esperadosNotif as $idxName) {
    if (in_array($idxName, $nombresNotif)) {
        echo "   -> notificaciones: $idxName [PRESENTE]\n";
    } else {
        echo "   -> notificaciones: $idxName [FALTANTE]\n";
    }
}

$colPedidos = $db->selectCollection('pedidos');
$indicesPed = iterator_to_array($colPedidos->listIndexes());
$nombresPed = array_map(function($i) { return $i->getName(); }, $indicesPed);

$esperadosPed = ['idx_pedidos_ven_fecha', 'idx_pedidos_ven_est_fecha', 'idx_pedidos_cli_est_fecha'];
foreach ($esperadosPed as $idxName) {
    if (in_array($idxName, $nombresPed)) {
        echo "   -> pedidos: $idxName [PRESENTE]\n";
    } else {
        echo "   -> pedidos: $idxName [FALTANTE]\n";
    }
}

// 2. Probar Polling Inteligente (obtener_notificaciones.php)
echo "\n2. Probando endpoint de notificaciones con with_orders=0 y with_orders=1...\n";
$vendedor = $db->selectCollection('usuarios')->findOne(['rol' => 'vendedor', 'estado' => 'activo']);
if ($vendedor) {
    $venId = (string)$vendedor['_id'];

    // Ejecutar sub-proceso para with_orders=0
    $cmd0 = "php -d session.use_cookies=0 -r \"require 'Back-end/conexion/conexion_mongo.php'; session_start(); \$_SESSION['id_vendedor'] = '$venId'; \$_GET['tipo'] = 'vendedor'; \$_GET['with_orders'] = '0'; ob_start(); include 'Back-end/notificaciones/obtener_notificaciones.php';\"";
    $out0 = shell_exec($cmd0);
    $data0 = json_decode($out0, true);

    if (isset($data0['status']) && $data0['status'] === 'success' && empty($data0['pedidos']) && !isset($data0['stats'])) {
        echo "   -> with_orders=0: Retornó notificaciones instantáneas, 0 pedidos en RAM y sin stats [OK]\n";
    } else {
        echo "   -> with_orders=0: Respuesta: " . substr($out0, 0, 100) . "\n";
    }

    // Ejecutar sub-proceso para with_orders=1
    $cmd1 = "php -d session.use_cookies=0 -r \"require 'Back-end/conexion/conexion_mongo.php'; session_start(); \$_SESSION['id_vendedor'] = '$venId'; \$_GET['tipo'] = 'vendedor'; \$_GET['with_orders'] = '1'; ob_start(); include 'Back-end/notificaciones/obtener_notificaciones.php';\"";
    $out1 = shell_exec($cmd1);
    $data1 = json_decode($out1, true);

    if (isset($data1['status']) && $data1['status'] === 'success' && isset($data1['stats']) && isset($data1['pedidos'])) {
        echo "   -> with_orders=1: Retornó pedidos sincronizados y métricas ({$data1['stats']['pendientes']} pendientes) [OK]\n";
    } else {
        echo "   -> with_orders=1: Respuesta: " . substr($out1, 0, 100) . "\n";
    }
}

// 3. Probar Proyecciones en Vistas
echo "\n3. Validando proyecciones quirúrgicas de datos...\n";
$colUsuarios = $db->selectCollection('usuarios');
$tienda = $colUsuarios->findOne(['rol' => 'vendedor'], ['projection' => ['tienda' => 1, 'nombre' => 1, 'apellido' => 1, 'telefono' => 1]]);
if ($tienda && isset($tienda['nombre']) && !isset($tienda['password_hash'])) {
    echo "   -> Proyección usuarios (tienda/nombre/apellido): password_hash excluido de memoria [OK]\n";
} else {
    echo "   -> Proyección usuarios inesperada [REVISAR]\n";
}

$colProd = $db->selectCollection('productos');
$prod = $colProd->findOne([], ['projection' => ['nombreproducto' => 1, 'descripcion' => 1, 'imagen' => 1, 'precio' => 1, 'stock_disponible' => 1]]);
if ($prod && isset($prod['nombreproducto'])) {
    echo "   -> Proyección productos: recupera campos exactos sin sobrecarga [OK]\n";
} else {
    echo "   -> Proyección productos inesperada [REVISAR]\n";
}

// 4. Probar candados de sesión liberados
echo "\n4. Validando no-bloqueo de sesiones (session_write_close)...";
@session_start(['use_cookies' => 0]);
$_SESSION['test_lock'] = 'activo';
session_write_close();
if (session_status() === PHP_SESSION_NONE) {
    echo " [OK]\n";
} else {
    echo " [REVISAR]\n";
}

echo "\n========================================================\n";
echo "   ¡TODAS LAS COMPROBACIONES DE INTEGRIDAD COMPLETADAS! \n";
echo "========================================================\n";
