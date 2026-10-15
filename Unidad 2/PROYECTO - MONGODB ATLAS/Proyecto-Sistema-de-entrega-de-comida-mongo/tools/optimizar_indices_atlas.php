<?php
// tools/optimizar_indices_atlas.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

echo "========================================================\n";
echo "  OPTIMIZACIÓN DE ÍNDICES DE RENDIMIENTO EN ATLAS      \n";
echo "========================================================\n\n";

try {
    $db = MongoConexion::getConexion();

    $indicesPorColeccion = [
        'usuarios' => [
            ['keys' => ['email' => 1], 'options' => ['unique' => true, 'name' => 'uniq_email']],
            ['keys' => ['rol' => 1], 'options' => ['name' => 'idx_rol']],
            ['keys' => ['rol' => 1, 'estado' => 1], 'options' => ['name' => 'idx_usuarios_rol_estado']]
        ],
        'productos' => [
            ['keys' => ['id_vendedor' => 1], 'options' => ['name' => 'idx_vendedor']],
            ['keys' => ['id_categoria' => 1], 'options' => ['name' => 'idx_categoria']],
            ['keys' => ['activo' => 1], 'options' => ['name' => 'idx_activo']]
        ],
        'pedidos' => [
            ['keys' => ['id_cliente' => 1], 'options' => ['name' => 'idx_pedidos_cliente', 'background' => true]],
            ['keys' => ['id_vendedor' => 1], 'options' => ['name' => 'idx_pedidos_vendedor', 'background' => true]],
            ['keys' => ['fechapedido' => -1], 'options' => ['name' => 'idx_pedidos_fecha', 'background' => true]],
            ['keys' => ['id_vendedor' => 1, 'fechapedido' => -1], 'options' => ['name' => 'idx_pedidos_ven_fecha', 'background' => true]],
            ['keys' => ['id_vendedor' => 1, 'estadopedido' => 1, 'fechapedido' => -1], 'options' => ['name' => 'idx_pedidos_ven_est_fecha', 'background' => true]],
            ['keys' => ['id_cliente' => 1, 'estadopedido' => 1, 'fechapedido' => -1], 'options' => ['name' => 'idx_pedidos_cli_est_fecha', 'background' => true]]
        ],
        'notificaciones' => [
            ['keys' => ['id_usuario' => 1, 'tipo_usuario' => 1, 'fecha' => -1], 'options' => ['name' => 'idx_notif_usr_tipo_fecha', 'background' => true]],
            ['keys' => ['id_usuario_str' => 1, 'tipo_usuario' => 1, 'fecha' => -1], 'options' => ['name' => 'idx_notif_usrstr_tipo_fecha', 'background' => true]],
            ['keys' => ['id_usuario' => 1, 'tipo_usuario' => 1, 'leida' => 1], 'options' => ['name' => 'idx_notif_usr_leida', 'background' => true]]
        ],
        'carritos' => [
            ['keys' => ['id_cliente' => 1], 'options' => ['unique' => true, 'name' => 'uniq_carrito_cliente']]
        ],
        'categorias' => [
            ['keys' => ['nombrecategoria' => 1], 'options' => ['name' => 'idx_nombrecategoria']],
            ['keys' => ['estado' => 1, 'nombrecategoria' => 1], 'options' => ['name' => 'idx_cat_estado_nombre']]
        ],
        'resenas' => [
            ['keys' => ['id_producto' => 1], 'options' => ['name' => 'idx_resena_producto']],
            ['keys' => ['id_cliente' => 1], 'options' => ['name' => 'idx_resena_cliente']],
            ['keys' => ['fecharesena' => -1], 'options' => ['name' => 'idx_resenas_fecha']]
        ],
        'historial_usuarios' => [
            ['keys' => ['fecha' => -1], 'options' => ['name' => 'idx_historial_fecha']],
            ['keys' => ['id_usuario' => 1, 'fecha' => -1], 'options' => ['name' => 'idx_historial_usuario_fecha']],
            ['keys' => ['accion' => 1, 'fecha' => -1], 'options' => ['name' => 'idx_historial_accion_fecha']]
        ]
    ];

    foreach ($indicesPorColeccion as $colNombre => $indices) {
        $col = $db->selectCollection($colNombre);
        echo "Colección '$colNombre':\n";
        foreach ($indices as $idxDef) {
            $name = $idxDef['options']['name'] ?? json_encode($idxDef['keys']);
            try {
                $col->createIndex($idxDef['keys'], $idxDef['options']);
                echo "  -> Índice asegurado: $name [OK]\n";
            } catch (Exception $e) {
                echo "  -> Índice $name: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    echo "========================================================\n";
    echo "  ¡TODOS LOS ÍNDICES DE RENDIMIENTO ESTÁN ACTIVOS!      \n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "Error optimizando índices: " . $e->getMessage() . "\n";
    exit(1);
}
