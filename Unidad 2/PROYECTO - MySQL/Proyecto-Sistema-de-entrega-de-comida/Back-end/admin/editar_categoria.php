<?php
session_start();

// 1. SEGURIDAD ADMIN
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
    $sql = "SELECT * FROM categoriaproducto WHERE idcategoria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $categoria = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$categoria) {
        die("Categoría no encontrada.");
    }
} else {
    header("Location: categorias.php");
    exit();
}

// 4. PROCESAR GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'];

    if (!empty($nombre)) {
        $sql_upd = "UPDATE categoriaproducto SET nombrecategoria=?, descripcion=?, estado=? WHERE idcategoria=?";
        $stmt = $conn->prepare($sql_upd);
        $stmt->bind_param("sssi", $nombre, $descripcion, $estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = "Categoría actualizada correctamente.";
            $tipo_mensaje = "success";
            // Actualizar vista
            $categoria['nombrecategoria'] = $nombre;
            $categoria['descripcion'] = $descripcion;
            $categoria['estado'] = $estado;
        } else {
            $mensaje = "Error al actualizar: " . $conn->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    } else {
        $mensaje = "El nombre es obligatorio.";
        $tipo_mensaje = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Categoría - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
    </style>
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
                        <a href="../admin/vendedores.php"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php" class="active"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>Editar Categoría #<?php echo $id; ?></h2>
                            <a href="categorias.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" style="max-width: 600px;">
                            
                            <div class="form-group">
                                <label>Nombre de la Categoría:</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($categoria['nombrecategoria']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Descripción:</label>
                                <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Estado:</label>
                                <select name="estado" class="form-control">
                                    <option value="activo" <?php if($categoria['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                    <option value="inactivo" <?php if($categoria['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
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