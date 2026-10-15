<?php
// tools/profile_productos.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$t0 = microtime(true);
$mongoDb = MongoConexion::getConexion();
$cat = $mongoDb->selectCollection("categorias")->findOne(["estado" => "activo"]);
$id_categoria = (string)$cat["_id"];
$t_conn = microtime(true);

echo "1. Conexion Mongo: " . round(($t_conn - $t0) * 1000, 2) . " ms\n";

$colCategorias = $mongoDb->selectCollection('categorias');
$colProductos = $mongoDb->selectCollection('productos');
$colUsuarios = $mongoDb->selectCollection('usuarios');

$t_col = microtime(true);
echo "2. Select collections: " . round(($t_col - $t_conn) * 1000, 2) . " ms\n";

$filtroCat = (preg_match('/^[a-f\d]{24}$/i', $id_categoria)) 
    ? ['_id' => new MongoDB\BSON\ObjectId($id_categoria)] 
    : ['$or' => [['_id' => (int)$id_categoria], ['_id' => (string)$id_categoria]]];

$categoria_actual = $colCategorias->findOne($filtroCat, ['projection' => ['nombrecategoria' => 1]]);
$t_cat = microtime(true);
echo "3. Query categoria findOne: " . round(($t_cat - $t_col) * 1000, 2) . " ms\n";

$filtroProd = [
    '$and' => [
        [
            '$or' => [
                ['id_categoria' => (preg_match('/^[a-f\d]{24}$/i', $id_categoria) ? new MongoDB\BSON\ObjectId($id_categoria) : $id_categoria)],
                ['id_categoria' => (string)$id_categoria],
                ['id_categoria' => (int)$id_categoria]
            ]
        ],
        [
            '$or' => [
                ['disponible' => ['$gt' => 0]],
                ['stock_disponible' => ['$gt' => 0]],
                ['disponible' => ['$exists' => false]]
            ]
        ]
    ]
];

$cursorProd = $colProductos->find($filtroProd, [
    'projection' => [
        'id_vendedor'      => 1,
        'imagen'           => 1,
        'stock_disponible' => 1,
        'disponible'       => 1,
        'nombreproducto'   => 1,
        'descripcion'      => 1,
        'precio'           => 1
    ]
])->toArray();
$t_prod = microtime(true);
echo "4. Query productos find toArray: " . round(($t_prod - $t_cat) * 1000, 2) . " ms (encontrados: " . count($cursorProd) . ")\n";

$vendorIds = [];
foreach ($cursorProd as $fila) {
    $idVen = $fila['id_vendedor'] ?? null;
    if ($idVen) {
        $vendorIds[] = (is_string($idVen) && preg_match('/^[a-f\d]{24}$/i', $idVen)) 
            ? new MongoDB\BSON\ObjectId($idVen) : $idVen;
    }
}

$vendedoresMap = [];
if (!empty($vendorIds)) {
    $uniqueVen = array_values(array_unique($vendorIds, SORT_REGULAR));
    $docsVen = $colUsuarios->find(
        ['_id' => ['$in' => $uniqueVen]],
        ['projection' => [
            'tienda'   => 1,
            'nombre'   => 1,
            'apellido' => 1
        ]]
    );
    foreach ($docsVen as $v) {
        $vendedoresMap[(string)$v['_id']] = $v;
    }
}
$t_ven = microtime(true);
echo "5. Query vendedores batch: " . round(($t_ven - $t_prod) * 1000, 2) . " ms\n";
echo "TIEMPO TOTAL BACKEND: " . round(($t_ven - $t0) * 1000, 2) . " ms\n";
