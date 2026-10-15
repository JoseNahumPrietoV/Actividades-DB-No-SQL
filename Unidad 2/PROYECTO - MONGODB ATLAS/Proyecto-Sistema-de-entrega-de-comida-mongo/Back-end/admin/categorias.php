<?php
session_start();

// 1. SEGURIDAD ADMIN
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../Front-end/login.php");
    exit();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$mensaje = "";
$tipo_mensaje = "";
$categorias = [];

try {
    $mongoDb = MongoConexion::getConexion();
    $colCategorias = $mongoDb->selectCollection('categorias');

    // 3. PROCESAR CREACIÓN DE CATEGORÍA (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = 'activo';
        $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!empty($nombre)) {
            $docCat = [
                'nombrecategoria' => $nombre,
                'descripcion'     => $descripcion,
                'estado'          => $estado,
                'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
            ];
            $resInsert = $colCategorias->insertOne($docCat);

            // Invalidar micro-caché de categorías para reflejo instantáneo en portada y catálogo
            unset($_SESSION['snackver_cache_categorias']);
            unset($_SESSION['snackver_cache_categorias_time']);

            if ($esAjax) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'    => 'success',
                    'message'   => 'Categoría creada con éxito.',
                    'categoria' => [
                        'id'              => (string)$resInsert->getInsertedId(),
                        'nombrecategoria' => $nombre,
                        'descripcion'     => $descripcion,
                        'estado'          => $estado
                    ]
                ]);
                exit;
            }

            $mensaje = "Categoría creada con éxito.";
            $tipo_mensaje = "success";
        } else {
            if ($esAjax) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'warning', 'message' => 'El nombre es obligatorio.']);
                exit;
            }
            $mensaje = "El nombre es obligatorio.";
            $tipo_mensaje = "warning";
        }
    }

    // Liberar candado de sesión tras procesar mutaciones
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // 4. CONSULTA DE CATEGORÍAS (con proyección quirúrgica)
    $cursor = $colCategorias->find([], [
        'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'estado' => 1],
        'sort'       => ['_id' => -1]
    ]);
    foreach ($cursor as $row) {
        $categorias[] = [
            'id'              => (string)$row['_id'],
            'nombrecategoria' => $row['nombrecategoria'] ?? '',
            'descripcion'     => $row['descripcion'] ?? '',
            'estado'          => $row['estado'] ?? 'activo'
        ];
    }
} catch (Exception $e) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $mensaje = "Error: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Admin Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_admin.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo" height="40"> Snackver Admin
            </a>
        </div>
    </header>

    <main class="dashboard-main-content">
        <div class="container">

            <div class="welcome-header">
                <h1>Gestión de Categorías</h1>
            </div>

            <div class="dashboard-grid" style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">

                <aside class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="../admin/dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php" class="active"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../admin/historial_usuarios.php"><i class="fas fa-history"></i> Historial de Usuarios</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">

                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                            <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                    <?php endif; ?>

                    <div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #f1c40f;">
                        <h3><i class="fas fa-plus-circle"></i> Crear Nueva Categoría</h3>
                        <form action="" method="POST" style="margin-top: 15px;">

                            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">

                                <div>
                                    <label style="font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Nombre:</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Bebidas" required>
                                </div>

                                <div>
                                    <label style="font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Descripción:</label>
                                    <input type="text" name="descripcion" class="form-control" placeholder="Breve descripción...">
                                </div>

                                <div>
                                    <button type="submit" class="btn-primary" style="height: 42px; display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px;">
                        <h3>Lista de Categorías</h3>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categorias)): ?>
                                        <?php foreach ($categorias as $row):
                                            $estado = $row['estado'];
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($row['nombrecategoria']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $estado; ?>">
                                                        <?php echo ucfirst($estado); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="editar_categoria.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; white-space: nowrap;">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center;">No hay categorías registradas.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formCat = document.querySelector('form[method="POST"]');
            if (!formCat) return;

            formCat.addEventListener('submit', function(e) {
                e.preventDefault();
                const btnSubmit = formCat.querySelector('button[type="submit"]');
                const textoOriginal = btnSubmit ? btnSubmit.innerHTML : '';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                }

                const formData = new FormData(formCat);
                fetch('', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.categoria) {
                        formCat.reset();
                        const tbody = document.querySelector('.table-container table tbody');
                        if (tbody) {
                            const filaVacia = tbody.querySelector('td[colspan="4"]');
                            if (filaVacia) filaVacia.closest('tr').remove();

                            const c = data.categoria;
                            const tr = document.createElement('tr');
                            const divEsc = document.createElement('div');
                            const esc = (t) => { divEsc.textContent = t || ''; return divEsc.innerHTML; };
                            const estCap = (c.estado || 'activo').charAt(0).toUpperCase() + (c.estado || 'activo').slice(1);

                            tr.innerHTML = `
                                <td><strong>${esc(c.nombrecategoria)}</strong></td>
                                <td>${esc(c.descripcion)}</td>
                                <td><span class="badge badge-${esc(c.estado)}">${esc(estCap)}</span></td>
                                <td>
                                    <a href="editar_categoria.php?id=${esc(c.id)}" class="btn-action btn-edit" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; white-space: nowrap;">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </td>
                            `;
                            tbody.prepend(tr);
                        }
                        if (typeof window.sincronizarVistaTiempoReal === 'function') {
                            window.sincronizarVistaTiempoReal();
                        }
                    } else if (data.message) {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error('Error al crear categoría:', err);
                })
                .finally(() => {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = textoOriginal;
                    }
                });
            });
        });
    </script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>

</body>

</html>