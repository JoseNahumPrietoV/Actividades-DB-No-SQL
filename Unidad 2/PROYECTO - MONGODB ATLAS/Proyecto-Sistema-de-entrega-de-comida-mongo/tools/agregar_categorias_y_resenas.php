<?php
// tools/agregar_categorias_y_resenas.php
// Inserta registros oficiales en las colecciones categorias y resenas en MongoDB Atlas

require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

echo "===================================================================\n";
echo "   INSERTANDO CATEGORÍAS Y RESEÑAS EN MONGODB ATLAS                \n";
echo "===================================================================\n\n";

try {
    $mongoDb = MongoConexion::getConexion();
    echo "✅ Conectado a MongoDB Atlas (Base de datos: " . $mongoDb->getDatabaseName() . ")\n\n";

    $colCategorias = $mongoDb->selectCollection('categorias');
    $colResenas    = $mongoDb->selectCollection('resenas');
    $colUsuarios   = $mongoDb->selectCollection('usuarios');

    // 1. Insertar Categorías
    echo "--- 1. Insertando Categorías ---\n";
    $categorias = [
        [
            'nombrecategoria' => 'Comida Rápida',
            'descripcion'     => 'Hamburguesas, hot dogs, tacos, pizzas y papas preparadas al momento.',
            'estado'          => 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'nombrecategoria' => 'Snacks y Antojitos',
            'descripcion'     => 'Dorilocos, esquites, chicharrones preparados, nachos y botanas.',
            'estado'          => 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'nombrecategoria' => 'Bebidas',
            'descripcion'     => 'Aguas frescas naturales, café frío/caliente, frappés y refrescos.',
            'estado'          => 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'nombrecategoria' => 'Postres y Dulces',
            'descripcion'     => 'Pasteles, rebanadas de pay, galletas artesanales, brownies y gelatinas.',
            'estado'          => 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'nombrecategoria' => 'Comida Casera',
            'descripcion'     => 'Guisados del día, empanadas, tortas, gorditas y antojitos mexicanos.',
            'estado'          => 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ]
    ];

    $catInsertadas = 0;
    foreach ($categorias as $cat) {
        $existe = $colCategorias->findOne(['nombrecategoria' => $cat['nombrecategoria']]);
        if (!$existe) {
            $res = $colCategorias->insertOne($cat);
            echo "   -> [NUEVA] Categoría creada: '{$cat['nombrecategoria']}' (ID: {$res->getInsertedId()})\n";
            $catInsertadas++;
        } else {
            echo "   -> [EXISTE] Categoría ya presente: '{$cat['nombrecategoria']}' (ID: {$existe['_id']})\n";
        }
    }
    echo "   Total categorías procesadas: " . count($categorias) . " (Nuevas insertadas: $catInsertadas)\n\n";

    // Obtener un ID de usuario para reseñas si existe
    $usuarioBase = $colUsuarios->findOne([], ['projection' => ['_id' => 1]]);
    $idUsuarioRef = $usuarioBase ? $usuarioBase['_id'] : new MongoDB\BSON\ObjectId();

    // 2. Insertar Reseñas
    echo "--- 2. Insertando Reseñas ---\n";
    $resenas = [
        [
            'id_producto'   => new MongoDB\BSON\ObjectId(),
            'id_cliente'    => $idUsuarioRef,
            'calificacion'  => 5,
            'comentario'    => '¡Excelente servicio y comida deliciosa! Llegó súper rápido a la explanada del campus.',
            'es_anonimo'    => false,
            'fecharesena'   => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'id_producto'   => new MongoDB\BSON\ObjectId(),
            'id_cliente'    => $idUsuarioRef,
            'calificacion'  => 5,
            'comentario'    => 'Muy buena porción y sazón casero, ideal para comer entre clases.',
            'es_anonimo'    => true,
            'fecharesena'   => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'id_producto'   => new MongoDB\BSON\ObjectId(),
            'id_cliente'    => $idUsuarioRef,
            'calificacion'  => 5,
            'comentario'    => 'Las bebidas estaban bien frías y a muy buen precio para estudiantes. 10/10.',
            'es_anonimo'    => false,
            'fecharesena'   => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'id_producto'   => new MongoDB\BSON\ObjectId(),
            'id_cliente'    => $idUsuarioRef,
            'calificacion'  => 4,
            'comentario'    => 'Los snacks llegaron calientitos y la atención fue muy amable y puntual.',
            'es_anonimo'    => true,
            'fecharesena'   => new MongoDB\BSON\UTCDateTime()
        ]
    ];

    $resInsertadas = 0;
    foreach ($resenas as $r) {
        $existe = $colResenas->findOne(['comentario' => $r['comentario']]);
        if (!$existe) {
            $ins = $colResenas->insertOne($r);
            $tipoAnonimo = $r['es_anonimo'] ? 'Anónima' : 'Pública';
            echo "   -> [NUEVA] Reseña {$r['calificacion']}★ ($tipoAnonimo): \"{$r['comentario']}\" (ID: {$ins->getInsertedId()})\n";
            $resInsertadas++;
        } else {
            echo "   -> [EXISTE] Reseña ya presente: \"{$r['comentario']}\"\n";
        }
    }
    echo "   Total reseñas procesadas: " . count($resenas) . " (Nuevas insertadas: $resInsertadas)\n\n";

    echo "===================================================================\n";
    echo "   ¡REGISTROS AGREGADOS CON ÉXITO EN MONGODB ATLAS!                \n";
    echo "===================================================================\n";

} catch (Exception $e) {
    echo "❌ Error al insertar registros en Atlas: " . $e->getMessage() . "\n";
}
