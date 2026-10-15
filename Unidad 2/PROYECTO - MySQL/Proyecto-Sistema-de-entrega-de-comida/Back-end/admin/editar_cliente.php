<?php
session_start();

// 1. SEGURIDAD: Verificar si es admin
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

// 3. OBTENER EL ID DEL CLIENTE (GET)
if (isset($_GET['id'])) {
    $id_cliente = intval($_GET['id']);

    // Buscar datos actuales
    $sql = "SELECT * FROM cliente WHERE idcliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $cliente = $result->fetch_assoc();
    } else {
        die("Cliente no encontrado.");
    }
    $stmt->close();
} else {
    header("Location: clientes.php");
    exit();
}

// 4. PROCESAR ACTUALIZACIÓN (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado']; // Agregamos opción para cambiar estado aquí también

    // Update SQL
    $sql_update = "UPDATE cliente SET 
                    nombre_cliente = ?, 
                    apellido_cliente = ?, 
                    email_cliente = ?, 
                    telefono_cliente = ?,
                    estado = ?
                   WHERE idcliente = ?";

    $stmt_upd = $conn->prepare($sql_update);
    $stmt_upd->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $estado, $id_cliente);

    if ($stmt_upd->execute()) {
        $mensaje = "Cliente actualizado correctamente.";
        $tipo_mensaje = "success";

        // Actualizar la variable visual $cliente para ver los cambios al instante
        $cliente['nombre_cliente'] = $nombre;
        $cliente['apellido_cliente'] = $apellido;
        $cliente['email_cliente'] = $email;
        $cliente['telefono_cliente'] = $telefono;
        $cliente['estado'] = $estado;
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "danger";
    }
    $stmt_upd->close();
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
                        <a href="../admin/clientes.php" class="active"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php" ><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>Editar Cliente #<?php echo $id_cliente; ?></h2>
                            <a href="clientes.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                                <?php echo $mensaje; ?>
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
<?php $conn->close(); ?>