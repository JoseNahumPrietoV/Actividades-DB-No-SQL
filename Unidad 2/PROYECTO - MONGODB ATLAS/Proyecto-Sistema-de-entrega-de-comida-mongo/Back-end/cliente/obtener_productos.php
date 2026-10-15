<?php
// Back-end/cliente/obtener_productos.php - Pure MongoDB Atlas
require_once __DIR__ . '/../conexion/conexion_mongo.php';

header('Content-Type: application/json');

$id_vendedor = isset($_GET['idvendedor']) ? trim($_GET['idvendedor']) : '';
$productos = [];

if (!empty($id_vendedor)) {
    try {
        $mongoDb = MongoConexion::getConexion();
        $colProductos = $mongoDb->selectCollection('productos');

        $filtro = [];
        if (preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
            $filtro['$or'] = [
                ['id_vendedor' => new MongoDB\BSON\ObjectId($id_vendedor)],
                ['id_vendedor' => $id_vendedor]
            ];
        } else {
            $filtro['$or'] = [
                ['id_vendedor' => (int)$id_vendedor],
                ['id_vendedor' => (string)$id_vendedor]
            ];
        }

        // Permitir productos con disponible > 0 o stock_disponible > 0 o sin el campo explícito si estado es activo
        $filtro['$and'] = [
            [
                '$or' => [
                    ['disponible' => ['$gt' => 0]],
                    ['stock_disponible' => ['$gt' => 0]],
                    ['disponible' => ['$exists' => false]]
                ]
            ]
        ];

        $cursor = $colProductos->find($filtro, [
            'projection' => [
                'nombreproducto' => 1
            ],
            'sort' => ['nombreproducto' => 1]
        ]);

        foreach ($cursor as $doc) {
            $productos[] = [
                'idproducto'     => (string)$doc['_id'],
                'nombreproducto' => $doc['nombreproducto'] ?? 'Sin nombre'
            ];
        }
    } catch (Exception $e) {
        error_log("Error en obtener_productos.php: " . $e->getMessage());
    }
}

echo json_encode($productos);