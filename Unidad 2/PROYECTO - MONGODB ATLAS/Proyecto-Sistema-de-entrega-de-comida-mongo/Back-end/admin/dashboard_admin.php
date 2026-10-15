<?php
session_start();

// 🔒 SEGURIDAD: Verificar que sea el admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../Front-end/login.php");
    exit();
}

// Liberar candado de sesión inmediatamente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$total_ventas = 0;
$total_usuarios = 0;
$total_vendedores = 0;

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos = $mongoDb->selectCollection('pedidos');
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    // Dinero total movido en pedidos
    $pipeline = [
        [
            '$group' => [
                '_id' => null,
                'total' => ['$sum' => '$total']
            ]
        ]
    ];
    $resVentas = $colPedidos->aggregate($pipeline)->toArray();
    $total_ventas = !empty($resVentas) ? (float)($resVentas[0]['total'] ?? 0) : 0.0;

    // Conteos optimizados de usuarios por rol en un solo round trip
    $resRoles = $colUsuarios->aggregate([
        ['$match' => ['rol' => ['$in' => ['cliente', 'vendedor']]]],
        ['$group' => ['_id' => '$rol', 'total' => ['$sum' => 1]]]
    ])->toArray();
    foreach ($resRoles as $r) {
        if ($r['_id'] === 'cliente') $total_usuarios = (int)$r['total'];
        if ($r['_id'] === 'vendedor') $total_vendedores = (int)$r['total'];
    }
} catch (Exception $e) {
    error_log("Error en dashboard_admin.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Administrador - Snackver</title>
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
                        <a href="../admin/historial_usuarios.php"><i class="fas fa-history"></i> Historial de Usuarios</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-card">

                    <p>Bienvenido, Jefe.</p>
                    <br>

                    <div class="dashboard-grid" style="display: flex; gap: 20px;">
                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Dinero Total Movido</h3>
                            <p id="kpi-admin-ventas" style="font-size: 2em; color: #27ae60; font-weight: bold;">$<?php echo number_format($total_ventas, 2); ?></p>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Clientes Registrados</h3>
                            <p id="kpi-admin-clientes" style="font-size: 2em; color: #3498db; font-weight: bold;"><?php echo $total_usuarios; ?></p>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1;">
                            <h3>Vendedores Registrados</h3>
                            <p id="kpi-admin-vendedores" style="font-size: 2em; color: #f39c12; font-weight: bold;"><?php echo $total_vendedores; ?></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>
</body>

</html>