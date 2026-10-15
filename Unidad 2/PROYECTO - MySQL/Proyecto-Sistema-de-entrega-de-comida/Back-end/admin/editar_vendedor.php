<?php
session_start();

// 1. SEGURIDAD: Verificar admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // header("Location: ../Front-end/login.php");
    // exit();
}

// 2. CONEXIÓN
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

$mensaje = "";
$tipo_mensaje = "";

// 3. OBTENER ID (GET)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM vendedor WHERE idvendedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $vendedor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$vendedor) {
        die("Vendedor no encontrado.");
    }
} else {
    header("Location: vendedores.php");
    exit();
}

// 4. PROCESAR GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $negocio = $_POST['negocio'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado'];

    $sql_upd = "UPDATE vendedor SET nombre_negocio=?, nombre_vendedor=?, apellido_vendedor=?, email_vendedor=?, telefono_vendedor=?, estado=? WHERE idvendedor=?";
    $stmt = $conn->prepare($sql_upd);
    $stmt->bind_param("ssssssi", $negocio, $nombre, $apellido, $email, $telefono, $estado, $id);

    if ($stmt->execute()) {
        $mensaje = "Vendedor actualizado correctamente.";
        $tipo_mensaje = "success";
        // Actualizar vista
        $vendedor['nombre_negocio'] = $negocio;
        $vendedor['nombre_vendedor'] = $nombre;
        $vendedor['apellido_vendedor'] = $apellido;
        $vendedor['email_vendedor'] = $email;
        $vendedor['telefono_vendedor'] = $telefono;
        $vendedor['estado'] = $estado;
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vendedor - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
   
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
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php" class="active"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>Editar Vendedor #<?php echo $id; ?></h2>
                            <a href="vendedores.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            
                            <div class="form-group">
                                <label>Nombre del Negocio:</label>
                                <input type="text" name="negocio" class="form-control" value="<?php echo htmlspecialchars($vendedor['nombre_negocio'] ?? ''); ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nombre Dueño:</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($vendedor['nombre_vendedor']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Apellido Dueño:</label>
                                    <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($vendedor['apellido_vendedor']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($vendedor['email_vendedor']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Teléfono:</label>
                                    <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($vendedor['telefono_vendedor']); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Estado de la Cuenta:</label>
                                <select name="estado" class="form-control">
                                    <option value="activo" <?php if($vendedor['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                    <option value="inactivo" <?php if($vendedor['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
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
<?php $conn->close(); ?>