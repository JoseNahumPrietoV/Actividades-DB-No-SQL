<?php
// tools/limpiar_datos_atlas.php
// Script de utilidad para vaciar todas las colecciones de la base de datos en MongoDB Atlas

require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';

echo "===================================================================\n";
echo "       VACIADO / LIMPIEZA DE DATOS EN MONGODB ATLAS (SNACKVER)      \n";
echo "===================================================================\n\n";

try {
    $mongoDb = MongoConexion::getConexion();
    $nombreDb = $mongoDb->getDatabaseName();
    echo " Conectado exitosamente a MongoDB Atlas.\n";
    echo " Base de datos objetivo: [ $nombreDb ]\n\n";

    $colecciones = ['usuarios', 'productos', 'pedidos', 'carritos', 'categorias', 'resenas', 'historial_usuarios'];

    echo "Contando registros actuales antes de limpiar:\n";
    foreach ($colecciones as $nombreCol) {
        $col = $mongoDb->selectCollection($nombreCol);
        $total = $col->countDocuments();
        echo "  - Colección '$nombreCol': $total documentos\n";
    }

    echo "\nIniciando borrado de registros...\n";
    $totalBorrados = 0;
    foreach ($colecciones as $nombreCol) {
        $col = $mongoDb->selectCollection($nombreCol);
        $res = $col->deleteMany([]);
        $borrados = $res->getDeletedCount();
        $totalBorrados += $borrados;
        echo "  - '$nombreCol': $borrados documentos eliminados.\n";
    }

    echo "\n Limpieza completada con éxito. Total de documentos eliminados: $totalBorrados\n";
    echo "Todas las colecciones en Atlas están ahora vacías (en blanco).\n\n";
    echo " Nota: Si deseas volver a poblar con datos semilla de prueba, ejecuta:\n";
    echo "   php tools/seed_mongo_initial_data.php\n";
    echo "===================================================================\n";

} catch (Exception $e) {
    echo " Error al limpiar la base de datos: " . $e->getMessage() . "\n";
}
