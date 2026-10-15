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

// 3. PROCESAR CREACIÓN DE CATEGORÍA (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $estado = 'activo'; // Por defecto nacen activas

    if (!empty($nombre)) {
        $sql_insert = "INSERT INTO categoriaproducto (nombrecategoria, descripcion, estado) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("sss", $nombre, $descripcion, $estado);

        if ($stmt->execute()) {
            $mensaje = "Categoría creada con éxito.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear: " . $conn->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    } else {
        $mensaje = "El nombre es obligatorio.";
        $tipo_mensaje = "warning";
    }
}

// 4. CONSULTA DE CATEGORÍAS
$sql = "SELECT * FROM categoriaproducto ORDER BY idcategoria DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Admin Snackver</title>
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
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">

                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                            <?php echo $mensaje; ?>
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
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()):
                                            $estado = isset($row['estado']) ? $row['estado'] : 'activo';
                                        ?>
                                            <tr>
                                                <td>#<?php echo $row['idcategoria']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['nombrecategoria']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $estado; ?>">
                                                        <?php echo ucfirst($estado); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="editar_categoria.php?id=<?php echo $row['idcategoria']; ?>" class="btn-action btn-edit">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center;">No hay categorías registradas.</td>
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

</body>

</html>
<?php $conn->close(); ?>