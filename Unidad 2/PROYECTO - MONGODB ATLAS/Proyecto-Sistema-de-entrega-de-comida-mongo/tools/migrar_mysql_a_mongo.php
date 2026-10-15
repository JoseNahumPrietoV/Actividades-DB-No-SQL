<?php
// tools/migrar_mysql_a_mongo.php
// Script de migración de MySQL (Tablas - listas.sql) a MongoDB Atlas para Snackver

require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';

echo "===================================================================\n";
echo "   MIGRACIÓN DE BASE DE DATOS: MySQL -> MONGODB ATLAS (Snackver)   \n";
echo "===================================================================\n\n";

// 1. Conexión a MySQL local
$host_sql = "127.0.0.1";
$user_sql = "root";
$pass_sql = "2884";
$db_sql   = "sistemawebsnackver";

$mysql = new mysqli($host_sql, $user_sql, $pass_sql, $db_sql);
if ($mysql->connect_error) {
    die("❌ Error conectando a MySQL local: " . $mysql->connect_error . "\n");
}
$mysql->set_charset("utf8mb4");
echo "✅ Conectado a MySQL local ($db_sql)\n";

// 2. Conexión a MongoDB Atlas
try {
    $mongoDb = MongoConexion::getConexion();
    echo "✅ Conectado a MongoDB Atlas (Base de datos: " . $mongoDb->getDatabaseName() . ")\n\n";
} catch (Exception $e) {
    die("❌ Error conectando a MongoDB Atlas: " . $e->getMessage() . "\n");
}

// Limpiar colecciones anteriores para una importación limpia
echo "--- 0. Limpiando colecciones anteriores en MongoDB Atlas ---\n";
$colCategorias = $mongoDb->selectCollection('categorias');
$colUsuarios   = $mongoDb->selectCollection('usuarios');
$colProductos  = $mongoDb->selectCollection('productos');
$colPedidos    = $mongoDb->selectCollection('pedidos');
$colResenas    = $mongoDb->selectCollection('resenas');
$colCarritos   = $mongoDb->selectCollection('carritos');

$colCategorias->deleteMany([]);
$colUsuarios->deleteMany([]);
$colProductos->deleteMany([]);
$colPedidos->deleteMany([]);
$colResenas->deleteMany([]);
$colCarritos->deleteMany([]);
echo "   -> Colecciones limpiadas exitosamente.\n\n";

// Mapas para relacionar IDs relacionales (MySQL) con ObjectIds de Mongo
$mapClientes   = [];
$mapVendedores = [];
$mapCategorias = [];
$mapProductos  = [];

// -------------------------------------------------------------
// FASE A: MIGRAR CATEGORÍAS (categoriaproducto)
// -------------------------------------------------------------
echo "--- 1. Migrando Categorías (categoriaproducto) ---\n";
$resCat = $mysql->query("SELECT * FROM categoriaproducto");
$countCat = 0;

if ($resCat) {
    while ($cat = $resCat->fetch_assoc()) {
        $doc = [
            'id_legacy'       => (int)$cat['idcategoria'],
            'nombrecategoria' => $cat['nombrecategoria'],
            'descripcion'     => $cat['descripcion'] ?? '',
            'estado'          => $cat['estado'] ?? 'activo',
            'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
        ];
        $insertResult = $colCategorias->insertOne($doc);
        $mapCategorias[$cat['idcategoria']] = $insertResult->getInsertedId();
        $countCat++;
    }
}
echo "   -> $countCat categorías migradas a la colección 'categorias'.\n\n";

// -------------------------------------------------------------
// FASE B: MIGRAR USUARIOS (cliente, vendedor y admin)
// -------------------------------------------------------------
echo "--- 2. Migrando Usuarios (cliente, vendedor y admin) ---\n";

// Admin predeterminado
$adminPassHash = password_hash('root', PASSWORD_DEFAULT);
$colUsuarios->insertOne([
    'nombre'        => 'Administrador',
    'apellido'      => 'Supremo',
    'email'         => 'root@root.com',
    'password_hash' => $adminPassHash,
    'telefono'      => '',
    'rol'           => 'admin',
    'estado'        => 'activo',
    'fecharegistro' => new MongoDB\BSON\UTCDateTime()
]);
echo "   -> Usuario Administrador (root@root.com / pass: root) asegurado.\n";

$countUsuarios = 1;

// Migrar Clientes
$resCli = $mysql->query("SELECT * FROM cliente");
if ($resCli) {
    while ($cli = $resCli->fetch_assoc()) {
        $pass = $cli['password_cliente'];
        if (!str_starts_with($pass, '$2y$') && !str_starts_with($pass, '$2a$')) {
            $pass = password_hash($pass, PASSWORD_DEFAULT);
        }

        $doc = [
            'id_legacy'     => (int)$cli['idcliente'],
            'nombre'        => $cli['nombre_cliente'],
            'apellido'      => $cli['apellido_cliente'],
            'email'         => strtolower(trim($cli['email_cliente'])),
            'password_hash' => $pass,
            'telefono'      => $cli['telefono_cliente'] ?? '',
            'rol'           => 'cliente',
            'estado'        => $cli['estado'] ?? 'activo',
            'fecharegistro' => new MongoDB\BSON\UTCDateTime(strtotime($cli['fecharegistro'] ?? 'now') * 1000)
        ];

        $insert = $colUsuarios->insertOne($doc);
        $mapClientes[$cli['idcliente']] = $insert->getInsertedId();
        $countUsuarios++;
    }
}

// Migrar Vendedores
$resVen = $mysql->query("SELECT * FROM vendedor");
if ($resVen) {
    while ($ven = $resVen->fetch_assoc()) {
        $pass = $ven['password_vendedor'];
        if (!str_starts_with($pass, '$2y$') && !str_starts_with($pass, '$2a$')) {
            $pass = password_hash($pass, PASSWORD_DEFAULT);
        }

        $doc = [
            'id_legacy'     => (int)$ven['idvendedor'],
            'nombre'        => $ven['nombre_vendedor'],
            'apellido'      => $ven['apellido_vendedor'],
            'email'         => strtolower(trim($ven['email_vendedor'])),
            'password_hash' => $pass,
            'telefono'      => $ven['telefono_vendedor'] ?? '',
            'rol'           => 'vendedor',
            'estado'        => $ven['estado'] ?? 'activo',
            'tienda'        => [
                'nombre_negocio' => $ven['nombre_negocio'] ?? '',
                'descripcion'    => $ven['descripcion'] ?? '',
                'direccion'      => $ven['dirección'] ?? ''
            ],
            'fecharegistro' => new MongoDB\BSON\UTCDateTime(strtotime($ven['fecharegistro'] ?? 'now') * 1000)
        ];

        $insert = $colUsuarios->insertOne($doc);
        $mapVendedores[$ven['idvendedor']] = $insert->getInsertedId();
        $countUsuarios++;
    }
}
echo "   -> $countUsuarios usuarios migrados a la colección 'usuarios'.\n\n";

// -------------------------------------------------------------
// FASE C: MIGRAR PRODUCTOS (producto)
// -------------------------------------------------------------
echo "--- 3. Migrando Productos (producto) ---\n";
$resProd = $mysql->query("SELECT * FROM producto");
$countProd = 0;

if ($resProd) {
    while ($prod = $resProd->fetch_assoc()) {
        $idVenMongo = $mapVendedores[$prod['idvendedor']] ?? null;
        $idCatMongo = $mapCategorias[$prod['idcategoria']] ?? null;

        $doc = [
            'id_legacy'        => (int)$prod['idproducto'],
            'id_vendedor'      => $idVenMongo,
            'id_categoria'     => $idCatMongo,
            'nombreproducto'   => $prod['nombreproducto'],
            'descripcion'      => $prod['descripcion'] ?? '',
            'precio'           => (float)$prod['precio'],
            'stock_disponible' => (int)$prod['disponible'],
            'disponible'       => (int)$prod['disponible'],
            'imagen'           => $prod['imagen'] ?? 'img/default-food.png',
            'activo'           => ((int)$prod['disponible'] > 0)
        ];

        $insert = $colProductos->insertOne($doc);
        $mapProductos[$prod['idproducto']] = $insert->getInsertedId();
        $countProd++;
    }
}
echo "   -> $countProd productos migrados a la colección 'productos'.\n\n";

// -------------------------------------------------------------
// FASE D: MIGRAR PEDIDOS CON DETALLE EMBEBIDO (pedido + detallepedido)
// -------------------------------------------------------------
echo "--- 4. Migrando Pedidos con Detalle Embebido (pedido + detallepedido) ---\n";
$resPed = $mysql->query("SELECT * FROM pedido");
$countPed = 0;

if ($resPed) {
    while ($ped = $resPed->fetch_assoc()) {
        $idCliMongo = $mapClientes[$ped['idcliente']] ?? null;
        $idVenMongo = $mapVendedores[$ped['idvendedor']] ?? null;

        // Obtener los productos embebidos desde detallepedido
        $idPedSql = (int)$ped['idpedido'];
        $resDet = $mysql->query("SELECT dp.*, p.nombreproducto 
                                 FROM detallepedido dp 
                                 LEFT JOIN producto p ON dp.idproducto = p.idproducto 
                                 WHERE dp.idpedido = $idPedSql");
        $items = [];
        if ($resDet) {
            while ($item = $resDet->fetch_assoc()) {
                $items[] = [
                    'id_producto'     => $mapProductos[$item['idproducto']] ?? null,
                    'nombreproducto'  => $item['nombreproducto'] ?? 'Platillo',
                    'cantidad'        => (int)$item['cantidad'],
                    'precio_unitario' => (float)$item['precio_unitario'],
                    'importe'         => (float)($item['importe'] ?? ($item['cantidad'] * $item['precio_unitario']))
                ];
            }
        }

        $doc = [
            'id_legacy'            => $idPedSql,
            'id_cliente'           => $idCliMongo,
            'id_vendedor'          => $idVenMongo,
            'items'                => $items,
            'total'                => (float)$ped['total'],
            'estadopedido'         => $ped['estadopedido'] ?? 'Pendiente',
            'metodopago'           => $ped['metodopago'] ?? 'Efectivo',
            'referenciapago'       => $ped['referenciapago'] ?? '',
            'notificado_cliente'   => (int)($ped['notificacion_cliente'] ?? 1),
            'notificado_vendedor'  => (int)($ped['notificacion_vendedor'] ?? 1),
            'fechapedido'          => new MongoDB\BSON\UTCDateTime(strtotime($ped['fechapedido'] ?? 'now') * 1000)
        ];

        $colPedidos->insertOne($doc);
        $countPed++;
    }
}
echo "   -> $countPed pedidos con detalle embebido migrados a 'pedidos'.\n\n";

// -------------------------------------------------------------
// FASE E: MIGRAR RESEÑAS (resena)
// -------------------------------------------------------------
echo "--- 5. Migrando Reseñas (resena) ---\n";
$resRes = $mysql->query("SELECT * FROM resena");
$countRes = 0;

if ($resRes) {
    while ($r = $resRes->fetch_assoc()) {
        $doc = [
            'id_legacy'    => (int)$r['idresena'],
            'id_producto'  => $mapProductos[$r['idproducto']] ?? null,
            'id_cliente'   => $mapClientes[$r['idcliente']] ?? null,
            'calificacion' => (int)$r['calificacion'],
            'comentario'   => $r['comentario'] ?? '',
            'fecharesena'  => new MongoDB\BSON\UTCDateTime(strtotime($r['fecharesena'] ?? 'now') * 1000)
        ];
        $colResenas->insertOne($doc);
        $countRes++;
    }
}
echo "   -> $countRes reseñas migradas a 'resenas'.\n\n";

// -------------------------------------------------------------
// FASE F: CREACIÓN DE ÍNDICES DE SEGURIDAD Y RENDIMIENTO
// -------------------------------------------------------------
echo "--- 6. Creando Índices en MongoDB Atlas ---\n";
$colUsuarios->createIndex(['email' => 1], ['unique' => true]);
$colUsuarios->createIndex(['rol' => 1]);
$colProductos->createIndex(['id_vendedor' => 1]);
$colProductos->createIndex(['id_categoria' => 1]);
$colPedidos->createIndex(['id_cliente' => 1]);
$colPedidos->createIndex(['id_vendedor' => 1]);
$colResenas->createIndex(['id_producto' => 1]);
echo "   -> Índices de optimización y unicidad creados.\n\n";

echo "===================================================================\n";
echo "🎉 ¡MIGRACIÓN COMPLETADA CON ÉXITO! Todos los datos están en MongoDB Atlas.\n";
echo "===================================================================\n";
