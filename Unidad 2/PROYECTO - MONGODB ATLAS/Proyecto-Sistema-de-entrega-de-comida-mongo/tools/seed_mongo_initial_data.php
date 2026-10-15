<?php
// tools/seed_mongo_initial_data.php
// Inicializa la base de datos MongoDB Atlas con el esquema completo de Tablas - listas.sql y datos semilla

require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';

echo "===================================================================\n";
echo "   POBLANDO MONGODB ATLAS CON EL ESQUEMA DE TABLAS - LISTAS.SQL    \n";
echo "===================================================================\n\n";

try {
    $mongoDb = MongoConexion::getConexion();
    echo "✅ Conectado a MongoDB Atlas (Base de datos: " . $mongoDb->getDatabaseName() . ")\n\n";

    $colCategorias = $mongoDb->selectCollection('categorias');
    $colUsuarios   = $mongoDb->selectCollection('usuarios');
    $colProductos  = $mongoDb->selectCollection('productos');
    $colPedidos    = $mongoDb->selectCollection('pedidos');
    $colResenas    = $mongoDb->selectCollection('resenas');
    $colCarritos   = $mongoDb->selectCollection('carritos');

    // 1. Limpiar colecciones para un inicio limpio
    $colCategorias->deleteMany([]);
    $colUsuarios->deleteMany([]);
    $colProductos->deleteMany([]);
    $colPedidos->deleteMany([]);
    $colResenas->deleteMany([]);
    $colCarritos->deleteMany([]);
    echo "🧹 Colecciones limpiadas correctamente.\n\n";

    // 2. CATEGORÍAS (categoriaproducto)
    echo "--- 1. Creando Categorías ---\n";
    $catsData = [
        [
            'nombrecategoria' => 'Comida Rápida',
            'descripcion'     => 'Hamburguesas, hot dogs, tacos, pizzas y papas preparadas al momento.',
            'estado'          => 'activo'
        ],
        [
            'nombrecategoria' => 'Snacks y Antojitos',
            'descripcion'     => 'Dorilocos, esquites, chicharrones preparados, nachos y botanas.',
            'estado'          => 'activo'
        ],
        [
            'nombrecategoria' => 'Bebidas',
            'descripcion'     => 'Aguas frescas naturales, café frío/caliente, frappés y refrescos.',
            'estado'          => 'activo'
        ],
        [
            'nombrecategoria' => 'Postres y Dulces',
            'descripcion'     => 'Pasteles, rebanadas de pay, galletas artesanales, brownies y gelatinas.',
            'estado'          => 'activo'
        ],
        [
            'nombrecategoria' => 'Comida Casera',
            'descripcion'     => 'Guisados del día, empanadas, tortas, gorditas y antojitos mexicanos.',
            'estado'          => 'activo'
        ]
    ];

    $mapCategorias = [];
    foreach ($catsData as $cat) {
        $doc = array_merge($cat, ['fecha_creacion' => new MongoDB\BSON\UTCDateTime()]);
        $ins = $colCategorias->insertOne($doc);
        $mapCategorias[$cat['nombrecategoria']] = $ins->getInsertedId();
        echo "   -> Categoría creada: {$cat['nombrecategoria']} (ID: {$ins->getInsertedId()})\n";
    }
    echo "\n";

    // 3. USUARIOS (cliente, vendedor, admin)
    echo "--- 2. Creando Usuarios Iniciales (Admin, Vendedor, Cliente) ---\n";
    
    // A) Admin
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
    $insAdmin = $colUsuarios->insertOne($adminDoc);
    echo "   -> Admin creado: root@root.com / pass: root\n";

    // B) Vendedor con Tienda
    $vendedorDoc = [
        'nombre'        => 'Bianca Patricia',
        'apellido'      => 'Ramos Gómez',
        'email'         => 'bianca.vendedora@snackver.com',
        'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
        'telefono'      => '2299887766',
        'rol'           => 'vendedor',
        'estado'        => 'activo',
        'tienda'        => [
            'nombre_negocio' => 'Snacks Universitarios La Güera',
            'descripcion'    => 'Los mejores snacks, aguas frescas y comida rápida preparados con amor universitario.',
            'direccion'      => 'Edificio B, explanada central, mesa 4'
        ],
        'fecharegistro' => new MongoDB\BSON\UTCDateTime()
    ];
    $insVen = $colUsuarios->insertOne($vendedorDoc);
    $idVendedor = $insVen->getInsertedId();
    echo "   -> Vendedor creado: {$vendedorDoc['email']} / pass: 123456\n";

    // C) Cliente
    $clienteDoc = [
        'nombre'        => 'Carlos',
        'apellido'      => 'Mendoza Pérez',
        'email'         => 'carlos.cliente@snackver.com',
        'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
        'telefono'      => '2291234567',
        'rol'           => 'cliente',
        'estado'        => 'activo',
        'fecharegistro' => new MongoDB\BSON\UTCDateTime()
    ];
    $insCli = $colUsuarios->insertOne($clienteDoc);
    $idCliente = $insCli->getInsertedId();
    echo "   -> Cliente creado: {$clienteDoc['email']} / pass: 123456\n\n";

    // 4. PRODUCTOS (producto)
    echo "--- 3. Creando Productos (Platillos) ---\n";
    $prodsData = [
        [
            'nombre'      => 'Hamburguesa Especial con Queso y Tocino',
            'categoria'   => 'Comida Rápida',
            'descripcion' => 'Carne 100% de res, queso manchego derretido, tocino crujiente, lechuga, jitomate y aderezo de la casa.',
            'precio'      => 65.00,
            'stock'       => 20,
            'imagen'      => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80'
        ],
        [
            'nombre'      => 'Orden de Tacos al Pastor (5 pzas)',
            'categoria'   => 'Comida Rápida',
            'descripcion' => '5 tacos con carne al pastor marinada tradicional, piña asada, cebolla, cilantro y salsas artesanales.',
            'precio'      => 45.00,
            'stock'       => 30,
            'imagen'      => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&auto=format&fit=crop&q=80'
        ],
        [
            'nombre'      => 'Dorilocos Preparados Especiales',
            'categoria'   => 'Snacks y Antojitos',
            'descripcion' => 'Bolsa de Doritos con jícama, pepino, cueritos, cacahuates japoneses, chamoy, miguelito y salsa Valentina.',
            'precio'      => 40.00,
            'stock'       => 25,
            'imagen'      => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?w=600&auto=format&fit=crop&q=80'
        ],
        [
            'nombre'      => 'Agua Natural de Horchata Artesanal (1 Litro)',
            'categoria'   => 'Bebidas',
            'descripcion' => 'Agua fresca de horchata elaborada con arroz, canela y toque de vainilla. Súper fría.',
            'precio'      => 25.00,
            'stock'       => 35,
            'imagen'      => 'https://images.unsplash.com/photo-1556881286-fc6915169721?w=600&auto=format&fit=crop&q=80'
        ],
        [
            'nombre'      => 'Frappé de Café Moka con Crema Batida',
            'categoria'   => 'Bebidas',
            'descripcion' => 'Café espresso frappé con jarabe de chocolate, leche entera y copete de crema batida con chispas.',
            'precio'      => 50.00,
            'stock'       => 15,
            'imagen'      => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80'
        ],
        [
            'nombre'      => 'Rebanada de Pay de Limón Casero',
            'categoria'   => 'Postres y Dulces',
            'descripcion' => 'Cremoso pay frío de limón con base crujiente de galleta Marías y ralladura fresca.',
            'precio'      => 30.00,
            'stock'       => 15,
            'imagen'      => 'https://images.unsplash.com/photo-1519869325930-281384150729?w=600&auto=format&fit=crop&q=80'
        ]
    ];

    $mapProductos = [];
    foreach ($prodsData as $p) {
        $doc = [
            'id_vendedor'      => $idVendedor,
            'id_categoria'     => $mapCategorias[$p['categoria']] ?? null,
            'nombreproducto'   => $p['nombre'],
            'descripcion'      => $p['descripcion'],
            'precio'           => (float)$p['precio'],
            'stock_disponible' => (int)$p['stock'],
            'disponible'       => (int)$p['stock'],
            'imagen'           => $p['imagen'],
            'activo'           => true,
            'fecha_creacion'   => new MongoDB\BSON\UTCDateTime()
        ];
        $insP = $colProductos->insertOne($doc);
        $mapProductos[$p['nombre']] = $insP->getInsertedId();
        echo "   -> Producto creado: {$p['nombre']} - \${$p['precio']}\n";
    }
    echo "\n";

    // 5. RESEÑAS (resena)
    echo "--- 4. Creando Reseñas de Ejemplo ---\n";
    $idBurger = $mapProductos['Hamburguesa Especial con Queso y Tocino'] ?? null;
    $idHorchata = $mapProductos['Agua Natural de Horchata Artesanal (1 Litro)'] ?? null;
    $idDorilocos = $mapProductos['Dorilocos Preparados Especiales'] ?? null;

    $resenasData = [
        [
            'id_producto'  => $idBurger,
            'id_cliente'   => $idCliente,
            'calificacion' => 5,
            'comentario'   => '¡La mejor hamburguesa del campus! La carne está deliciosa y la entrega fue súper rápida.',
            'fecharesena'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'id_producto'  => $idDorilocos,
            'id_cliente'   => $idCliente,
            'calificacion' => 5,
            'comentario'   => 'Muy bien preparados, bastante jícama y cueritos. Excelente opción para la tarde.',
            'fecharesena'  => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'id_producto'  => $idHorchata,
            'id_cliente'   => $idCliente,
            'calificacion' => 4,
            'comentario'   => 'Agua súper fría y con muy buen toque de canela. Se agradece con el calor.',
            'fecharesena'  => new MongoDB\BSON\UTCDateTime()
        ]
    ];

    foreach ($resenasData as $r) {
        $colResenas->insertOne($r);
    }
    echo "   -> 3 reseñas creadas para mostrar en el carrusel de inicio.\n\n";

    // 6. PEDIDO DE EJEMPLO CON ITEMS EMBEBIDOS (pedido + detallepedido)
    echo "--- 5. Creando Pedido de Ejemplo con Detalle Embebido ---\n";
    $pedidoDoc = [
        'id_cliente'          => $idCliente,
        'id_vendedor'         => $idVendedor,
        'items'               => [
            [
                'id_producto'     => $idBurger,
                'nombreproducto'  => 'Hamburguesa Especial con Queso y Tocino',
                'cantidad'        => 1,
                'precio_unitario' => 65.00,
                'importe'         => 65.00
            ],
            [
                'id_producto'     => $idHorchata,
                'nombreproducto'  => 'Agua Natural de Horchata Artesanal (1 Litro)',
                'cantidad'        => 1,
                'precio_unitario' => 25.00,
                'importe'         => 25.00
            ]
        ],
        'total'               => 90.00,
        'estadopedido'        => 'Entregado',
        'metodopago'          => 'Efectivo',
        'referenciapago'      => 'Pago en mano al recibir',
        'notificado_cliente'  => 1,
        'notificado_vendedor' => 1,
        'fechapedido'         => new MongoDB\BSON\UTCDateTime(),
        'fechaentrega'        => new MongoDB\BSON\UTCDateTime()
    ];
    $colPedidos->insertOne($pedidoDoc);
    echo "   -> Pedido entregado de ejemplo creado ($90.00 MXN).\n\n";

    // 7. ÍNDICES DE RENDIMIENTO Y SEGURIDAD
    echo "--- 6. Creando Índices en MongoDB Atlas ---\n";
    $colUsuarios->createIndex(['email' => 1], ['unique' => true]);
    $colUsuarios->createIndex(['rol' => 1]);
    $colProductos->createIndex(['id_vendedor' => 1]);
    $colProductos->createIndex(['id_categoria' => 1]);
    $colPedidos->createIndex(['id_cliente' => 1]);
    $colPedidos->createIndex(['id_vendedor' => 1]);
    $colResenas->createIndex(['id_producto' => 1]);
    echo "   -> Índices de unicidad y optimización creados con éxito.\n\n";

    echo "===================================================================\n";
    echo "🎉 ¡MONGODB ATLAS COMPLETAMENTE CONFIGURADO Y POBLADO CON ÉXITO!  \n";
    echo "===================================================================\n";

} catch (Exception $e) {
    die("❌ Error poblando MongoDB Atlas: " . $e->getMessage() . "\n");
}
