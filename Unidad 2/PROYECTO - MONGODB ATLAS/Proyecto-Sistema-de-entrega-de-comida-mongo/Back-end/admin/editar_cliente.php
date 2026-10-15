<?php
session_start();

// 1. SEGURIDAD: Verificar si es admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../Front-end/login.php");
    exit();
}

// Liberar candado de sesión inmediatamente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$mensaje = "";
$tipo_mensaje = "";
$id_cliente = $_GET['id'] ?? '';

if (empty($id_cliente)) {
    header("Location: clientes.php");
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $filtro = [];
    if (preg_match('/^[a-f\d]{24}$/i', $id_cliente)) {
        $filtro['_id'] = new MongoDB\BSON\ObjectId($id_cliente);
    } else {
        $filtro['$or'] = [
            ['_id' => (int)$id_cliente],
            ['_id' => (string)$id_cliente]
        ];
    }

    $doc = $colUsuarios->findOne($filtro);
    if (!$doc) {
        die("Cliente no encontrado.");
    }

    $cliente = [
        'nombre_cliente'   => $doc['nombre'] ?? '',
        'apellido_cliente' => $doc['apellido'] ?? '',
        'email_cliente'    => $doc['email'] ?? '',
        'telefono_cliente' => $doc['telefono'] ?? '',
        'estado'           => $doc['estado'] ?? 'activo'
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $estado = trim($_POST['estado'] ?? 'activo');

        $colUsuarios->updateOne(
            $filtro,
            ['$set' => [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'email'    => $email,
                'telefono' => $telefono,
                'estado'   => $estado
            ]]
        );

        $mensaje = "Cliente actualizado correctamente.";
        $tipo_mensaje = "success";

        $cliente['nombre_cliente'] = $nombre;
        $cliente['apellido_cliente'] = $apellido;
        $cliente['email_cliente'] = $email;
        $cliente['telefono_cliente'] = $telefono;
        $cliente['estado'] = $estado;
    }
} catch (Exception $e) {
    $mensaje = "Error: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Admin</title>
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
            <div class="dashboard-grid" style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">

                <aside class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="../admin/dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php" class="active"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../admin/historial_usuarios.php"><i class="fas fa-history"></i> Historial de Usuarios</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>Editar Cliente #<?php echo substr((string)$id_cliente, -6); ?></h2>
                            <a href="clientes.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                                <?php echo htmlspecialchars($mensaje); ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nombre:</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($cliente['nombre_cliente']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Apellido:</label>
                                    <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($cliente['apellido_cliente']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email_cliente']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Teléfono:</label>
                                    <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($cliente['telefono_cliente']); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Estado de la Cuenta:</label>
                                <select name="estado" class="form-control">
                                    <option value="activo" <?php if ($cliente['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                    <option value="inactivo" <?php if ($cliente['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                                </select>
                            </div>

                            <div style="margin-top: 20px; text-align: right;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>

</body>

</html>