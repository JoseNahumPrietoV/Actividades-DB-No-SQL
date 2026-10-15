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

// 3. CONSULTA DE VENDEDORES
$sql = "SELECT * FROM vendedor";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Vendedor - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_admin.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver Admin
            </a>
        </div>
    </header>

    <main class="dashboard-main-content">
        <div class="container">

            <div class="welcome-header">
                <h1>Bienvenido, Jefe.</h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="../admin/dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php" class="active"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../conexion/cerrar_sesion.php""><i class=" fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>


                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px;">

                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h2>Lista de Vendedores</h2>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Negocio</th>
                                        <th>Dueño</th>
                                        <th>Contacto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()):
                                            $id = $row['idvendedor'];
                                            $negocio = htmlspecialchars($row['nombre_negocio'] ?? 'Sin Negocio');
                                            $nombre = htmlspecialchars($row['nombre_vendedor'] . ' ' . $row['apellido_vendedor']);
                                            $email = htmlspecialchars($row['email_vendedor']);
                                            $telefono = htmlspecialchars($row['telefono_vendedor']);
                                            $estado = isset($row['estado']) ? $row['estado'] : 'activo';
                                        ?>
                                            <tr id="fila-<?php echo $id; ?>">
                                                <td>#<?php echo $id; ?></td>
                                                <td>
                                                    <div style="font-weight:bold; color:#2c3e50;"><?php echo $negocio; ?></div>
                                                </td>
                                                <td><?php echo $nombre; ?></td>
                                                <td style="font-size: 0.9em;">
                                                    <div><i class="fas fa-envelope"></i> <?php echo $email; ?></div>
                                                    <div><i class="fas fa-phone"></i> <?php echo $telefono; ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo ($estado == 'activo') ? 'badge-active' : 'badge-inactive'; ?>">
                                                        <?php echo ucfirst($estado); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="editar_vendedor.php?id=<?php echo $id; ?>" class="btn-action btn-edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <?php if ($estado == 'activo'): ?>
                                                        <button class="btn-action btn-danger" onclick="cambiarEstadoVendedor(<?php echo $id; ?>, 'inactivo')" title="Inactivar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-action btn-success" onclick="cambiarEstadoVendedor(<?php echo $id; ?>, 'activo')" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding: 20px;">No hay vendedores registrados.</td>
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
        function cambiarEstadoVendedor(idVendedor, nuevoEstado) {
            if (!confirm('¿Estás seguro de cambiar el estado de este vendedor a ' + nuevoEstado + '?')) return;

            const formData = new FormData();
            formData.append('id', idVendedor);
            formData.append('estado', nuevoEstado);

            fetch('actualizar_estado_vendedor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>