<?php
// Back-end/conexion/sincronizar_tiempo_real.php
// Endpoint unificado de sincronización diferencial en tiempo real (0ms con hash de estado)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$vista = trim($_GET['vista'] ?? '');
$hashCliente = trim($_GET['hash'] ?? '');

$rolSesion      = $_SESSION['rol'] ?? '';
$idClienteSes   = $_SESSION['id_cliente'] ?? null;
$idVendedorSes  = $_SESSION['id_vendedor'] ?? null;

// Liberar candado de sesión de inmediato para permitir concurrencia total (0 bloqueo)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (empty($vista)) {
    echo json_encode(['status' => 'error', 'message' => 'Vista no especificada']);
    exit;
}

require_once __DIR__ . '/conexion_mongo.php';

/**
 * Helper para parsear User-Agent igual que en historial_usuarios.php
 */
function parseDispositivoTiempoReal($userAgent) {
    if (empty($userAgent) || $userAgent === 'Desconocido') {
        return ['icono' => 'fas fa-question-circle', 'nombre' => 'Desconocido'];
    }
    $so = 'Desconocido';
    $icono = 'fas fa-desktop';
    if (stripos($userAgent, 'Windows NT 10.0') !== false || stripos($userAgent, 'Windows NT 11.0') !== false) {
        $so = 'Windows 10/11';
        $icono = 'fab fa-windows';
    } elseif (stripos($userAgent, 'Windows') !== false) {
        $so = 'Windows';
        $icono = 'fab fa-windows';
    } elseif (stripos($userAgent, 'Android') !== false) {
        $so = 'Android';
        $icono = 'fab fa-android';
    } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
        $so = 'iOS';
        $icono = 'fab fa-apple';
    } elseif (stripos($userAgent, 'Mac OS') !== false || stripos($userAgent, 'Macintosh') !== false) {
        $so = 'macOS';
        $icono = 'fab fa-apple';
    } elseif (stripos($userAgent, 'Linux') !== false) {
        $so = 'Linux';
        $icono = 'fab fa-linux';
    }

    $nav = '';
    if (stripos($userAgent, 'Edg') !== false) {
        $nav = 'Edge';
    } elseif (stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false) {
        $nav = 'Opera';
    } elseif (stripos($userAgent, 'Chrome') !== false) {
        $nav = 'Chrome';
    } elseif (stripos($userAgent, 'Firefox') !== false) {
        $nav = 'Firefox';
    } elseif (stripos($userAgent, 'Safari') !== false) {
        $nav = 'Safari';
    }

    $resumen = $nav ? "$so • $nav" : $so;
    return ['icono' => $icono, 'nombre' => $resumen];
}

try {
    $mongoDb = MongoConexion::getConexion();
    $payload = [];

    switch ($vista) {
        // =========================================================================
        // 1. PORTADA PÚBLICA (index.php: Categorías y Reseñas de Estudiantes)
        // =========================================================================
        case 'portada':
            $colCategorias = $mongoDb->selectCollection('categorias');
            $colResenas    = $mongoDb->selectCollection('resenas');
            $colUsuarios   = $mongoDb->selectCollection('usuarios');
            $colProductos  = $mongoDb->selectCollection('productos');

            $categorias = [];
            $cursorCat = $colCategorias->find(
                ['estado' => 'activo'],
                [
                    'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'imagen' => 1],
                    'sort'       => ['nombrecategoria' => 1]
                ]
            );
            foreach ($cursorCat as $catDoc) {
                $categorias[] = [
                    'idcategoria'     => (string)$catDoc['_id'],
                    'nombrecategoria' => $catDoc['nombrecategoria'] ?? '',
                    'descripcion'     => $catDoc['descripcion'] ?? '',
                    'imagen'          => $catDoc['imagen'] ?? ''
                ];
            }

            $resenas = [];
            $cursorR = $colResenas->find([], ['sort' => ['fecharesena' => -1], 'limit' => 9])->toArray();
            if (!empty($cursorR)) {
                $clientIds = [];
                $productIds = [];
                foreach ($cursorR as $rDoc) {
                    $idCli = $rDoc['id_cliente'] ?? null;
                    if ($idCli) {
                        $clientIds[] = (is_string($idCli) && preg_match('/^[a-f\d]{24}$/i', $idCli))
                            ? new MongoDB\BSON\ObjectId($idCli) : $idCli;
                    }
                    $idProd = $rDoc['id_producto'] ?? null;
                    if ($idProd) {
                        $productIds[] = (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd))
                            ? new MongoDB\BSON\ObjectId($idProd) : $idProd;
                    }
                }

                $clientesMap = [];
                if (!empty($clientIds)) {
                    $docsCli = $colUsuarios->find(
                        ['_id' => ['$in' => array_values(array_unique($clientIds, SORT_REGULAR))]],
                        ['projection' => ['nombre' => 1, 'apellido' => 1]]
                    );
                    foreach ($docsCli as $u) {
                        $clientesMap[(string)$u['_id']] = $u;
                    }
                }

                $productosMap = [];
                $vendorIds = [];
                if (!empty($productIds)) {
                    $docsProd = $colProductos->find(
                        ['_id' => ['$in' => array_values(array_unique($productIds, SORT_REGULAR))]],
                        ['projection' => ['nombreproducto' => 1, 'id_vendedor' => 1]]
                    );
                    foreach ($docsProd as $p) {
                        $productosMap[(string)$p['_id']] = $p;
                        $idVen = $p['id_vendedor'] ?? null;
                        if ($idVen) {
                            $vendorIds[] = (is_string($idVen) && preg_match('/^[a-f\d]{24}$/i', $idVen))
                                ? new MongoDB\BSON\ObjectId($idVen) : $idVen;
                        }
                    }
                }

                $vendedoresMap = [];
                if (!empty($vendorIds)) {
                    $docsVen = $colUsuarios->find(
                        ['_id' => ['$in' => array_values(array_unique($vendorIds, SORT_REGULAR))]],
                        ['projection' => ['tienda' => 1, 'nombre' => 1, 'apellido' => 1]]
                    );
                    foreach ($docsVen as $v) {
                        $vendedoresMap[(string)$v['_id']] = $v;
                    }
                }

                foreach ($cursorR as $rDoc) {
                    $esAnonimo = !empty($rDoc['es_anonimo']);
                    $nomCliente = "Estudiante";
                    if ($esAnonimo) {
                        $nomCliente = "Estudiante Anónimo";
                    } else {
                        $idCliStr = isset($rDoc['id_cliente']) ? (string)$rDoc['id_cliente'] : '';
                        if (isset($clientesMap[$idCliStr])) {
                            $uDoc = $clientesMap[$idCliStr];
                            $nomCliente = trim(($uDoc['nombre'] ?? '') . ' ' . ($uDoc['apellido'] ?? '')) ?: 'Estudiante';
                        }
                    }

                    $nomProd = "Platillo";
                    $nomVend = "Snackver";
                    $idProdStr = isset($rDoc['id_producto']) ? (string)$rDoc['id_producto'] : '';
                    if (isset($productosMap[$idProdStr])) {
                        $pDoc = $productosMap[$idProdStr];
                        $nomProd = $pDoc['nombreproducto'] ?? 'Platillo';
                        $idVenStr = isset($pDoc['id_vendedor']) ? (string)$pDoc['id_vendedor'] : '';
                        if (isset($vendedoresMap[$idVenStr])) {
                            $vDoc = $vendedoresMap[$idVenStr];
                            $tienda = $vDoc['tienda'] ?? [];
                            $nomVend = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($vDoc['nombre'] ?? '') . ' ' . ($vDoc['apellido'] ?? ''));
                        }
                    }

                    $fechaStr = date('d/m/Y');
                    if (isset($rDoc['fecharesena']) && $rDoc['fecharesena'] instanceof MongoDB\BSON\UTCDateTime) {
                        $fechaStr = $rDoc['fecharesena']->toDateTime()->format('d/m/Y');
                    } elseif (!empty($rDoc['fecharesena'])) {
                        $fechaStr = date('d/m/Y', strtotime((string)$rDoc['fecharesena']));
                    }

                    $resenas[] = [
                        'calificacion'   => (int)($rDoc['calificacion'] ?? 5),
                        'comentario'     => $rDoc['comentario'] ?? '',
                        'nombreCompleto' => $nomCliente,
                        'producto'       => $nomProd,
                        'vendedor'       => $nomVend,
                        'fecha'          => $fechaStr
                    ];
                }
            }

            $payload = [
                'categorias' => $categorias,
                'resenas'    => $resenas
            ];
            break;

        // =========================================================================
        // 2. PRODUCTOS PÚBLICOS (Front-end/Productos.php y resultados_busqueda.php)
        // =========================================================================
        case 'productos_publicos':
            $idCategoria = trim($_GET['id_categoria'] ?? '');
            $busqueda    = trim($_GET['busqueda'] ?? '');
            $colCategorias = $mongoDb->selectCollection('categorias');
            $colProductos  = $mongoDb->selectCollection('productos');
            $colUsuarios   = $mongoDb->selectCollection('usuarios');

            $items = [];
            $categoriaActiva = true;
            $nombreCategoria = '';

            if (!empty($idCategoria)) {
                $filtroCat = (preg_match('/^[a-f\d]{24}$/i', $idCategoria))
                    ? ['_id' => new MongoDB\BSON\ObjectId($idCategoria), 'estado' => 'activo']
                    : ['$or' => [['_id' => (int)$idCategoria], ['_id' => (string)$idCategoria]], 'estado' => 'activo'];

                $catDoc = $colCategorias->findOne($filtroCat, ['projection' => ['nombrecategoria' => 1]]);
                if (!$catDoc) {
                    $categoriaActiva = false;
                } else {
                    $nombreCategoria = $catDoc['nombrecategoria'] ?? 'Categoría';
                    $filtroProd = [
                        '$and' => [
                            [
                                '$or' => [
                                    ['id_categoria' => (preg_match('/^[a-f\d]{24}$/i', $idCategoria) ? new MongoDB\BSON\ObjectId($idCategoria) : $idCategoria)],
                                    ['id_categoria' => (string)$idCategoria],
                                    ['id_categoria' => (int)$idCategoria]
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
                }
            } elseif (!empty($busqueda)) {
                $regex = new MongoDB\BSON\Regex($busqueda, 'i');
                $filtroProd = [
                    '$and' => [
                        [
                            '$or' => [
                                ['nombreproducto' => $regex],
                                ['descripcion'    => $regex]
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
            }

            if ($categoriaActiva && isset($filtroProd)) {
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
                    $docsVen = $colUsuarios->find(
                        ['_id' => ['$in' => array_values(array_unique($vendorIds, SORT_REGULAR))]],
                        ['projection' => ['tienda' => 1, 'nombre' => 1, 'apellido' => 1]]
                    );
                    foreach ($docsVen as $v) {
                        $vendedoresMap[(string)$v['_id']] = $v;
                    }
                }

                foreach ($cursorProd as $fila) {
                    $idVen = $fila['id_vendedor'] ?? null;
                    $seller_business = "Vendedor Snackver";
                    $personal_name = "Estudiante Vendedor";

                    if ($idVen) {
                        $idVenStr = (string)$idVen;
                        if (isset($vendedoresMap[$idVenStr])) {
                            $vendedor = $vendedoresMap[$idVenStr];
                            $tienda = $vendedor['tienda'] ?? [];
                            $seller_business = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''));
                            $personal_name = trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''));
                        }
                    }

                    $imgRaw = $fila['imagen'] ?? '';
                    $ruta_imagen = !empty($imgRaw) ? (str_starts_with($imgRaw, 'http') ? $imgRaw : "../" . $imgRaw) : "../img/default-food.png";
                    $stock = isset($fila['stock_disponible']) ? (int)$fila['stock_disponible'] : (int)($fila['disponible'] ?? 0);

                    $items[] = [
                        'id'            => (string)$fila['_id'],
                        'image'         => $ruta_imagen,
                        'name'          => $fila['nombreproducto'] ?? '',
                        'desc'          => $fila['descripcion'] ?? '',
                        'seller'        => $seller_business,
                        'personal_name' => $personal_name,
                        'price'         => '$' . number_format((float)($fila['precio'] ?? 0), 2),
                        'stock'         => $stock
                    ];
                }
            }

            $payload = [
                'categoria_activa' => $categoriaActiva,
                'nombre_categoria' => $nombreCategoria,
                'items'            => $items
            ];
            break;

        // =========================================================================
        // 3. ADMIN DASHBOARD (Back-end/admin/dashboard_admin.php)
        // =========================================================================
        case 'admin_dashboard':
            if ($rolSesion !== 'admin') {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colPedidos  = $mongoDb->selectCollection('pedidos');
            $colUsuarios = $mongoDb->selectCollection('usuarios');

            $resVentas = $colPedidos->aggregate([
                ['$group' => ['_id' => null, 'total' => ['$sum' => '$total']]]
            ])->toArray();
            $totalVentas = !empty($resVentas) ? (float)($resVentas[0]['total'] ?? 0) : 0.0;

            $totalClientes   = 0;
            $totalVendedores = 0;
            $resRoles = $colUsuarios->aggregate([
                ['$match' => ['rol' => ['$in' => ['cliente', 'vendedor']]]],
                ['$group' => ['_id' => '$rol', 'total' => ['$sum' => 1]]]
            ])->toArray();
            foreach ($resRoles as $r) {
                if ($r['_id'] === 'cliente') $totalClientes = (int)$r['total'];
                if ($r['_id'] === 'vendedor') $totalVendedores = (int)$r['total'];
            }

            $payload = [
                'total_ventas_fmt' => '$' . number_format($totalVentas, 2),
                'total_clientes'   => $totalClientes,
                'total_vendedores' => $totalVendedores
            ];
            break;

        // =========================================================================
        // 4. ADMIN CLIENTES (Back-end/admin/clientes.php)
        // =========================================================================
        case 'admin_clientes':
            if ($rolSesion !== 'admin') {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colUsuarios = $mongoDb->selectCollection('usuarios');
            $cursor = $colUsuarios->find(['rol' => 'cliente'], [
                'sort' => ['fecharegistro' => -1],
                'projection' => [
                    'nombre'         => 1,
                    'apellido'       => 1,
                    'email'          => 1,
                    'telefono'       => 1,
                    'estado'         => 1,
                    'fecharegistro'  => 1,
                    'fecha_registro' => 1
                ]
            ]);

            $clientes = [];
            foreach ($cursor as $doc) {
                $fechaRegStr = 'No registrada';
                $fDoc = $doc['fecharegistro'] ?? $doc['fecha_registro'] ?? null;
                if ($fDoc instanceof MongoDB\BSON\UTCDateTime) {
                    $fechaRegStr = $fDoc->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i');
                } elseif (is_numeric($fDoc)) {
                    $fechaRegStr = date('d/m/Y H:i', (int)($fDoc / 1000));
                }

                $clientes[] = [
                    'id'            => (string)$doc['_id'],
                    'nombre'        => trim(($doc['nombre'] ?? '') . ' ' . ($doc['apellido'] ?? '')),
                    'email'         => $doc['email'] ?? '',
                    'telefono'      => $doc['telefono'] ?? '',
                    'estado'        => $doc['estado'] ?? 'activo',
                    'fecharegistro' => $fechaRegStr
                ];
            }

            $payload = ['clientes' => $clientes];
            break;

        // =========================================================================
        // 5. ADMIN VENDEDORES (Back-end/admin/vendedores.php)
        // =========================================================================
        case 'admin_vendedores':
            if ($rolSesion !== 'admin') {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colUsuarios = $mongoDb->selectCollection('usuarios');
            $cursor = $colUsuarios->find(['rol' => 'vendedor'], [
                'sort' => ['fecharegistro' => -1],
                'projection' => [
                    'tienda'         => 1,
                    'nombre_negocio' => 1,
                    'nombre'         => 1,
                    'apellido'       => 1,
                    'email'          => 1,
                    'telefono'       => 1,
                    'estado'         => 1,
                    'fecharegistro'  => 1,
                    'fecha_registro' => 1
                ]
            ]);

            $vendedores = [];
            foreach ($cursor as $row) {
                $tienda = $row['tienda'] ?? [];
                $fechaRegStr = 'No registrada';
                $fDoc = $row['fecharegistro'] ?? $row['fecha_registro'] ?? null;
                if ($fDoc instanceof MongoDB\BSON\UTCDateTime) {
                    $fechaRegStr = $fDoc->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i');
                } elseif (is_numeric($fDoc)) {
                    $fechaRegStr = date('d/m/Y H:i', (int)($fDoc / 1000));
                }

                $vendedores[] = [
                    'id'            => (string)$row['_id'],
                    'negocio'       => $tienda['nombre_negocio'] ?? ($row['nombre_negocio'] ?? 'Sin Negocio'),
                    'nombre'        => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
                    'email'         => $row['email'] ?? '',
                    'telefono'      => $row['telefono'] ?? '',
                    'estado'        => $row['estado'] ?? 'activo',
                    'fecharegistro' => $fechaRegStr
                ];
            }

            $payload = ['vendedores' => $vendedores];
            break;

        // =========================================================================
        // 6. ADMIN CATEGORÍAS (Back-end/admin/categorias.php)
        // =========================================================================
        case 'admin_categorias':
            if ($rolSesion !== 'admin') {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colCategorias = $mongoDb->selectCollection('categorias');
            $cursor = $colCategorias->find([], [
                'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'estado' => 1],
                'sort'       => ['_id' => -1]
            ]);
            $categorias = [];
            foreach ($cursor as $row) {
                $categorias[] = [
                    'id'              => (string)$row['_id'],
                    'nombrecategoria' => $row['nombrecategoria'] ?? '',
                    'descripcion'     => $row['descripcion'] ?? '',
                    'estado'          => $row['estado'] ?? 'activo'
                ];
            }
            $payload = ['categorias' => $categorias];
            break;

        // =========================================================================
        // 7. ADMIN HISTORIAL DE USUARIOS (Back-end/admin/historial_usuarios.php)
        // =========================================================================
        case 'admin_historial':
            if ($rolSesion !== 'admin') {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colHistorial = $mongoDb->selectCollection('historial_usuarios');
            $cursor = $colHistorial->find([], [
                'sort'       => ['fecha' => -1],
                'limit'      => 200,
                'projection' => [
                    'id_usuario'  => 1,
                    'email'       => 1,
                    'rol'         => 1,
                    'accion'      => 1,
                    'ip'          => 1,
                    'dispositivo' => 1,
                    'fecha'       => 1
                ]
            ]);

            $historial = [];
            foreach ($cursor as $doc) {
                $fechaStr = 'Desconocida';
                if (isset($doc['fecha']) && $doc['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                    $fechaStr = $doc['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i:s');
                } elseif (isset($doc['fecha']) && is_numeric($doc['fecha'])) {
                    $fechaStr = date('d/m/Y H:i:s', (int)($doc['fecha'] / 1000));
                }

                $accion = $doc['accion'] ?? 'Evento';
                $rol    = $doc['rol'] ?? 'usuario';
                $disp   = $doc['dispositivo'] ?? 'Desconocido';
                $devInfo = parseDispositivoTiempoReal($disp);

                $badgeClase = 'badge-accion-default';
                $iconoAccion = 'fa-info-circle';
                if (stripos($accion, 'Inicio de sesión') !== false) {
                    $badgeClase = 'badge-accion-login';
                    $iconoAccion = 'fa-sign-in-alt';
                } elseif (stripos($accion, 'Registro') !== false) {
                    $badgeClase = 'badge-accion-registro';
                    $iconoAccion = 'fa-user-plus';
                } elseif (stripos($accion, '2FA') !== false) {
                    $badgeClase = 'badge-accion-otp';
                    $iconoAccion = 'fa-shield-alt';
                } elseif (stripos($accion, 'Cierre de sesión') !== false) {
                    $badgeClase = 'badge-accion-logout';
                    $iconoAccion = 'fa-sign-out-alt';
                } elseif (stripos($accion, 'eliminado') !== false) {
                    $badgeClase = 'badge-accion-delete';
                    $iconoAccion = 'fa-trash-alt';
                }

                $badgeRolClase = 'badge-rol-cliente';
                if ($rol === 'admin') $badgeRolClase = 'badge-rol-admin';
                elseif ($rol === 'vendedor') $badgeRolClase = 'badge-rol-vendedor';

                $historial[] = [
                    'id'                 => (string)$doc['_id'],
                    'id_usuario'         => isset($doc['id_usuario']) ? (string)$doc['id_usuario'] : '',
                    'email'              => $doc['email'] ?? 'Sin correo',
                    'rol'                => $rol,
                    'accion'             => $accion,
                    'ip'                 => $doc['ip'] ?? '127.0.0.1',
                    'dispositivo_raw'    => $disp,
                    'dispositivo_icono'  => $devInfo['icono'],
                    'dispositivo_nombre' => $devInfo['nombre'],
                    'badge_clase'        => $badgeClase,
                    'icono_accion'       => $iconoAccion,
                    'badge_rol_clase'    => $badgeRolClase,
                    'fecha_dia'          => substr($fechaStr, 0, 10),
                    'fecha_hora'         => substr($fechaStr, 11)
                ];
            }

            $payload = ['historial' => $historial];
            break;

        // =========================================================================
        // 8. VENDEDOR MIS PLATILLOS (Back-end/vendedor/Misplatillos.php)
        // =========================================================================
        case 'vendedor_platillos':
            if (!$idVendedorSes) {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colProductos = $mongoDb->selectCollection('productos');
            $filtro = [];
            if (is_string($idVendedorSes) && preg_match('/^[a-f\d]{24}$/i', $idVendedorSes)) {
                $filtro = ['$or' => [
                    ['id_vendedor' => new MongoDB\BSON\ObjectId($idVendedorSes)],
                    ['id_vendedor' => (string)$idVendedorSes],
                    ['id_vendedor' => (int)$idVendedorSes]
                ]];
            } else {
                $filtro = ['$or' => [
                    ['id_vendedor' => (int)$idVendedorSes],
                    ['id_vendedor' => (string)$idVendedorSes]
                ]];
            }

            $cursor = $colProductos->find($filtro, [
                'projection' => [
                    'nombreproducto'   => 1,
                    'descripcion'      => 1,
                    'imagen'           => 1,
                    'precio'           => 1,
                    'stock_disponible' => 1,
                    'disponible'       => 1
                ]
            ]);

            $productos = [];
            foreach ($cursor as $doc) {
                $stock = (int)($doc['stock_disponible'] ?? ($doc['disponible'] ?? 0));
                $claseStock = 'stock-ok';
                $textoStock = $stock . ' disponibles';
                if ($stock === 0) {
                    $claseStock = 'stock-out';
                    $textoStock = 'Agotado';
                } elseif ($stock < 5) {
                    $claseStock = 'stock-low';
                    $textoStock = '¡Solo quedan ' . $stock . '!';
                }

                $imgSrc = $doc['imagen'] ?? 'img/default-food.png';
                $rutaPlatilloImg = str_starts_with($imgSrc, 'http') ? $imgSrc : "../../" . $imgSrc;

                $productos[] = [
                    'idproducto'     => (string)$doc['_id'],
                    'nombreproducto' => $doc['nombreproducto'] ?? '',
                    'descripcion'    => $doc['descripcion'] ?? '',
                    'imagen'         => $rutaPlatilloImg,
                    'precio_fmt'     => '$' . number_format((float)($doc['precio'] ?? 0), 2),
                    'disponible'     => $stock,
                    'clase_stock'    => $claseStock,
                    'texto_stock'    => $textoStock
                ];
            }

            $payload = ['productos' => $productos];
            break;

        // =========================================================================
        // 9. CLIENTE BUSCAR TIENDAS (Back-end/cliente/BuscarTienda.php)
        // =========================================================================
        case 'cliente_tiendas':
            if (!$idClienteSes) {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $termino = trim($_GET['busqueda'] ?? '');
            $colUsuarios = $mongoDb->selectCollection('usuarios');
            $filtro = ['rol' => 'vendedor', 'estado' => 'activo'];

            if (!empty($termino)) {
                $regex = new MongoDB\BSON\Regex($termino, 'i');
                $filtro['$or'] = [
                    ['tienda.nombre_negocio' => $regex],
                    ['tienda.descripcion'    => $regex],
                    ['nombre'                => $regex]
                ];
            }

            $cursor = $colUsuarios->find($filtro, [
                'projection' => [
                    'tienda'   => 1,
                    'nombre'   => 1,
                    'apellido' => 1,
                    'telefono' => 1
                ]
            ]);

            $tiendas = [];
            foreach ($cursor as $doc) {
                $tiendaData = $doc['tienda'] ?? [];
                $nomNegocio = $tiendaData['nombre_negocio'] ?? (($doc['nombre'] ?? '') . ' Store');
                $fotoTienda = !empty($tiendaData['foto_negocio'])
                    ? $tiendaData['foto_negocio']
                    : "https://placehold.co/400x300/1A4F8B/ffffff?text=" . urlencode($nomNegocio);

                $tiendas[] = [
                    'idvendedor'        => (string)$doc['_id'],
                    'nombre_negocio'    => $nomNegocio,
                    'nombre_vendedor'   => $doc['nombre'] ?? '',
                    'apellido_vendedor' => $doc['apellido'] ?? '',
                    'direccion'         => $tiendaData['direccion'] ?? ($tiendaData['dirección'] ?? ''),
                    'descripcion'       => $tiendaData['descripcion'] ?? 'Descripción no disponible',
                    'foto_negocio'      => $fotoTienda
                ];
            }

            $payload = ['tiendas' => $tiendas];
            break;

        // =========================================================================
        // 10. CLIENTE VER TIENDA (Back-end/cliente/ver_tienda.php)
        // =========================================================================
        case 'cliente_ver_tienda':
            if (!$idClienteSes) {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $idTienda = trim($_GET['id_tienda'] ?? '');
            if (empty($idTienda)) {
                echo json_encode(['status' => 'error', 'message' => 'Tienda no especificada']);
                exit;
            }
            $colProductos = $mongoDb->selectCollection('productos');
            $variantesId = [
                ['id_vendedor' => (string)$idTienda],
                ['id_vendedor' => (int)$idTienda]
            ];
            if (preg_match('/^[a-f\d]{24}$/i', $idTienda)) {
                $variantesId[] = ['id_vendedor' => new MongoDB\BSON\ObjectId($idTienda)];
            }

            $filtroProd = [
                '$and' => [
                    ['$or' => $variantesId],
                    ['stock_disponible' => ['$gt' => 0]]
                ]
            ];

            $cursorProd = $colProductos->find($filtroProd, [
                'projection' => [
                    'nombreproducto'   => 1,
                    'descripcion'      => 1,
                    'imagen'           => 1,
                    'precio'           => 1,
                    'stock_disponible' => 1
                ]
            ]);

            $productos = [];
            foreach ($cursorProd as $docP) {
                $imgRaw = $docP['imagen'] ?? '';
                $imagenProducto = !empty($imgRaw) ? (str_starts_with($imgRaw, 'http') ? $imgRaw : "../../" . $imgRaw) : "../../img/default-food.png";
                $descRaw = $docP['descripcion'] ?? '';
                $precioFloat = (float)($docP['precio'] ?? 0);

                $productos[] = [
                    'idproducto'     => (string)$docP['_id'],
                    'nombreproducto' => $docP['nombreproducto'] ?? '',
                    'descripcion'    => substr($descRaw, 0, 60) . '...',
                    'imagen'         => $imagenProducto,
                    'precio'         => $precioFloat,
                    'precio_fmt'     => number_format($precioFloat, 2),
                    'disponible'     => (int)($docP['stock_disponible'] ?? 0)
                ];
            }

            $payload = ['productos' => $productos];
            break;

        // =========================================================================
        // 11. CLIENTE CARRITO (Back-end/cliente/carrito.php)
        // =========================================================================
        case 'cliente_carrito':
            if (!$idClienteSes) {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colCarritos = $mongoDb->selectCollection('carritos');
            $filtroCli = [];
            if (is_string($idClienteSes) && preg_match('/^[a-f\d]{24}$/i', $idClienteSes)) {
                $filtroCli = ['$or' => [
                    ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteSes)],
                    ['id_cliente' => (string)$idClienteSes],
                    ['id_cliente' => (int)$idClienteSes]
                ]];
            } else {
                $filtroCli = ['$or' => [
                    ['id_cliente' => (int)$idClienteSes],
                    ['id_cliente' => (string)$idClienteSes]
                ]];
            }

            $cartDoc = $colCarritos->findOne($filtroCli);
            $items = [];
            $totalCarrito = 0.00;
            if ($cartDoc && !empty($cartDoc['items'])) {
                foreach ($cartDoc['items'] as $item) {
                    $cant = (int)($item['cantidad'] ?? 1);
                    $prec = (float)($item['precio'] ?? 0);
                    $subt = $cant * $prec;
                    $imgSrc = $item['imagen'] ?? '';
                    $rutaImg = str_starts_with($imgSrc, 'http') ? $imgSrc : "../../" . $imgSrc;

                    $items[] = [
                        'idproducto'     => (string)$item['idproducto'],
                        'nombreproducto' => $item['nombreproducto'] ?? 'Platillo',
                        'imagen'         => $rutaImg,
                        'cantidad'       => $cant,
                        'precio'         => $prec,
                        'precio_fmt'     => number_format($prec, 2),
                        'subtotal_fmt'   => number_format($subt, 2)
                    ];
                    $totalCarrito += $subt;
                }
            }

            $payload = [
                'items'        => $items,
                'subtotal_fmt' => '$' . number_format($totalCarrito, 2),
                'total_fmt'    => '$' . number_format($totalCarrito, 2)
            ];
            break;

        // =========================================================================
        // 12. CLIENTE RESEÑAS (Back-end/cliente/resenas.php - Selector Vendedores)
        // =========================================================================
        case 'cliente_resenas':
            if (!$idClienteSes) {
                echo json_encode(['status' => 'unauthenticated']);
                exit;
            }
            $colUsuarios = $mongoDb->selectCollection('usuarios');
            $cursorV = $colUsuarios->find(
                ['rol' => 'vendedor', 'estado' => 'activo'],
                ['projection' => ['tienda' => 1, 'nombre' => 1, 'apellido' => 1]]
            );
            $vendedores = [];
            foreach ($cursorV as $v) {
                $tienda = $v['tienda'] ?? [];
                $nombreMostrar = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($v['nombre'] ?? '') . ' ' . ($v['apellido'] ?? ''));
                $vendedores[] = [
                    'idvendedor'     => (string)$v['_id'],
                    'nombre_mostrar' => $nombreMostrar
                ];
            }
            $payload = ['vendedores' => $vendedores];
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Vista desconocida']);
            exit;
    }

    $hashActual = md5(json_encode($payload));

    if ($hashCliente !== '' && $hashCliente === $hashActual) {
        echo json_encode([
            'status' => 'unchanged',
            'hash'   => $hashActual
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'changed',
        'hash'   => $hashActual,
        'data'   => $payload
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
