<?php
// tools/inicializar_solo_admin.php
// Limpia todas las colecciones en MongoDB Atlas y deja únicamente el usuario Administrador

require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';

echo "===================================================================\n";
echo "   RESTAURAR BASE DE DATOS LIMPIA (SÓLO COLECCIONES Y ADMIN)      \n";
echo "===================================================================\n\n";

try {
    $mongoDb = MongoConexion::getConexion();
    echo " Conectado a MongoDB Atlas (Base de datos: " . $mongoDb->getDatabaseName() . ")\n\n";

    $colCategorias = $mongoDb->selectCollection('categorias');
    $colUsuarios   = $mongoDb->selectCollection('usuarios');
    $colProductos  = $mongoDb->selectCollection('productos');
    $colPedidos    = $mongoDb->selectCollection('pedidos');
    $colResenas    = $mongoDb->selectCollection('resenas');
    $colCarritos   = $mongoDb->selectCollection('carritos');
    $colHistorial  = $mongoDb->selectCollection('historial_usuarios');

    // 1. Limpiar todas las colecciones
    echo "1. Vaciando colecciones...\n";
    $colCategorias->deleteMany([]);
    $colUsuarios->deleteMany([]);
    $colProductos->deleteMany([]);
    $colPedidos->deleteMany([]);
    $colResenas->deleteMany([]);
    $colCarritos->deleteMany([]);
    $colHistorial->deleteMany([]);
    echo "    Todas las colecciones han sido vaciadas.\n\n";

    // 2. Insertar únicamente el Administrador base
    echo "2. Creando usuario Administrador base...\n";
    $adminDoc = [
        'nombre'        => 'Administrador',
        'apellido'      => 'Supremo',
        'email'         => 'root@root.com',
        'password_hash' => password_hash('root', PASSWORD_DEFAULT),
        'telefono'      => '2290000000',
        'rol'           => 'admin',
        'estado'        => 'activo',
        'fecharegistro' => new MongoDB\BSON\UTCDateTime()
    ];
    $colUsuarios->insertOne($adminDoc);
    echo "    Administrador creado:\n";
    echo "      - Email: root@root.com\n";
    echo "      - Contraseña: root\n";
    echo "      - Rol: admin\n\n";

    $colNotif = $mongoDb->selectCollection('notificaciones');
    $colNotif->deleteMany([]);

    // 3. Crear índices únicos y de rendimiento (protegidos contra nombres existentes)
    $indices = [
        [$colUsuarios, ['email' => 1], ['unique' => true]],
        [$colUsuarios, ['rol' => 1], []],
        [$colProductos, ['id_vendedor' => 1], []],
        [$colPedidos, ['id_cliente' => 1], []],
        [$colCarritos, ['id_cliente' => 1], ['unique' => true]],
        [$colNotif, ['id_usuario' => 1], []],
        [$colNotif, ['fecha' => -1], []]
    ];
    foreach ($indices as $idxDef) {
        try {
            $idxDef[0]->createIndex($idxDef[1], $idxDef[2]);
        } catch (\Throwable $ex) {}
    }
    echo "3. Índices de rendimiento y seguridad asegurados.\n\n";

    echo " Base de datos en estado inicial limpio lista para usar.\n";
    echo "Todos los nuevos clientes, vendedores, productos y pedidos se crearán\n";
    echo "a medida que interactúes con el sistema.\n";
    echo "===================================================================\n";

} catch (Exception $e) {
    echo " Error al inicializar: " . $e->getMessage() . "\n";
}
