<?php
// Back-end/conexion/actualizar_perfil_cliente.php
session_start();

require_once __DIR__ . '/pantalla_usuario_cliente.php';

if (!isset($_SESSION['id_cliente'])) {
    header('Location: ../../Front-end/login.php'); 
    exit();
}

$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email    = strtolower(trim($_POST['email'] ?? ''));

if (empty($nombre) || empty($apellido) || empty($telefono) || empty($email)) {
    $_SESSION['mensaje_perfil'] = 'Error: Todos los campos son obligatorios.';
    header('Location: ../cliente/Miperfil.php');
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $filtro = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtro = ['_id' => new MongoDB\BSON\ObjectId($idClienteLogueado)];
    } else {
        $filtro = ['$or' => [
            ['_id' => (int)$idClienteLogueado],
            ['_id' => (string)$idClienteLogueado]
        ]];
    }

    $colUsuarios->updateOne(
        $filtro,
        ['$set' => [
            'nombre'   => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'email'    => $email
        ]]
    );

    $_SESSION['mensaje_perfil'] = '✅ ¡Tu información se ha actualizado con éxito!';
    $_SESSION['nombre_cliente'] = $nombre;
    $_SESSION['apellido_cliente'] = $apellido;
    $_SESSION['email_cliente'] = $email;
    $_SESSION['datos_cliente'] = [
        'nombre_cliente'   => $nombre,
        'apellido_cliente' => $apellido,
        'nombre_completo'  => trim("$nombre $apellido"),
        'rol_label'        => 'Estudiante - Cliente',
        'telefono_cliente' => $telefono,
        'email_cliente'    => $email
    ];

} catch (Exception $e) {
    $_SESSION['mensaje_perfil'] = '❌ Error al actualizar la información: ' . $e->getMessage();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Location: ../cliente/Miperfil.php');
exit();
?>