<?php

// 💡 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? ''; // Mantengo 'mensaje_perfil' si ya lo usas para mostrar el resultado
unset($_SESSION['mensaje_perfil']); // Limpiar la variable después de mostrar

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css">
    <link rel="stylesheet" href="../css/resumen-cliente.css">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_cliente.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <div class="auth-buttons">
                <a href="carrito.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Carrito
                </a>
            </div>
        </div>
    </header>

    <main class="dashboard-main-content">
        <div class="container">
            <?php if (!empty($mensaje_perfil)): ?>
                <div class="alert" style="color: white; background-color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($mensaje_perfil); ?>
                </div>
            <?php endif; ?>
            <div class="welcome-header">
                <h1>Tu Cuenta <?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['rol_label'] ?? 'Estudiante - Cliente'); ?></p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php" class="active"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">

                    <div class="dashboard-card">
                        <h3>
                            <i class="fas fa-user-circle" style="color: #f1c40f;"></i>
                            Información Personal
                        </h3>
                        <p style="color: #777; margin-bottom: 25px; font-size: 0.95rem;">
                            Actualiza tu información personal para que los vendedores puedan contactarte.
                        </p>

                        <form class="profile-form" action="../conexion/actualizar_perfil_cliente.php" method="POST">

                            <div class="form-row-grid">
                                <div class="form-group">
                                    <label for="first-name">Nombre</label>
                                    <input type="text" id="first-name" name="nombre" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last-name">Apellido</label>
                                    <input type="text" id="last-name" name="apellido" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row-grid">
                                <div class="form-group">
                                    <label for="phone">Teléfono</label>
                                    <input type="text" id="phone" name="telefono" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_cliente['telefono_cliente'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_cliente['email_cliente'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div style="text-align: right; margin-top: 10px;">
                                <button type="submit" class="btn-save-all">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="dashboard-card">
                        <h3>Zona de Seguridad</h3>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <p style="margin: 0; color: #555; font-weight: 600;">Contraseña</p>
                                <p style="margin: 0; color: #888; font-size: 0.9rem;">Se recomienda cambiarla periódicamente.</p>
                            </div>
                            <a href="../../Front-end/cambiar_password.php" class="btn btn-secondary">
                                <i class="fas fa-lock"></i> Cambiar Contraseña
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>


</body>

</html>