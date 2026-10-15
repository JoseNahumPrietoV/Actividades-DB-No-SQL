<?php
session_start();

// 🔒 SEGURIDAD: Verificar que sea el admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, para afuera
    header("Location: ../../Front-end/login.php");
    exit();
}

// Conexión para ver datos (pero no para ver al admin, porque no existe en BD)
$conn = new mysqli("127.0.0.1", "root", "2884", "sistemawebsnackver");

// Consultas para ver estadísticas generales
$total_ventas = $conn->query("SELECT SUM(total) as total FROM pedido")->fetch_assoc()['total'];
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM cliente")->fetch_assoc()['total'];
$total_vendedores = $conn->query("SELECT COUNT(*) as total FROM vendedor")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Administrador - Snackver</title>
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
                        <a href="../admin/dashboard_admin.php" class="active"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../conexion/cerrar_sesion.php""><i class=" fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-card">

                    <p>Bienvenido, Jefe.</p>
                    <br>

                    <div class="dashboard-grid" style="display: flex; gap: 20px;">
                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Dinero Total Movido</h3>
                            <p style="font-size: 2em; color: #27ae60; font-weight: bold;">$<?php echo number_format($total_ventas, 2); ?></p>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Clientes Registrados</h3>
                            <p style="font-size: 2em; color: #3498db; font-weight: bold;"><?php echo $total_usuarios; ?></p>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Vendedores Activos</h3>
                            <p style="font-size: 2em; color: #f39c12; font-weight: bold;"><?php echo $total_vendedores; ?></p>
                        </div>
                    </div>


                </div>
</body>

</html>